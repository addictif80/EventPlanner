<?php

namespace App\Core;

use PDO;

/**
 * Applies pending, purely-additive schema migrations automatically (CREATE
 * TABLE IF NOT EXISTS / ADD COLUMN IF NOT EXISTS / INSERT IGNORE — safe to
 * rerun), so an admin deploying new code doesn't need SSH access to run
 * `mysql < migrations/xxx.sql` by hand. Tracked in `schema_migrations`, and
 * serialized across concurrent requests via GET_LOCK() so two PHP workers
 * booting at the same time never run the same ALTER TABLE twice at once.
 *
 * Migrations that mutate or restructure existing data (see 003_multi_tenant.sql)
 * are excluded on purpose — mark such a file with a `-- @manual-only` comment
 * on its first line (`// @manual-only` for a .php one) and this runner will
 * skip it, leaving it for a deliberate manual run by the operator.
 *
 * database/migrations also accepts `.php` files alongside `.sql` ones, for
 * anything a plain SQL script can't express — data seeding with PHP-side
 * logic (see 008_seed_legal_pages.php), one-off backfills, calls into
 * application code, etc. Such a file is a plain script (no class/namespace)
 * that receives a connected `$pdo` (PDO) and runs top to bottom; like SQL
 * migrations it must be idempotent (safe to run more than once) since that's
 * the fallback if the schema_migrations tracking row is ever lost, even
 * though it will normally only execute once per file. Both kinds are
 * discovered together, sorted by filename, so a shared numeric prefix
 * (007_, 008_, ...) keeps them running in the intended order regardless of
 * extension. Neither bin/ CLI scripts nor manual `mysql < ...`/`php ...`
 * commands should be needed anymore for anything that belongs here.
 */
class Migrator
{
    private const LOCK_NAME = 'eventplanner_schema_migrations';

    public static function runPending(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pending = self::pendingMigrations($pdo);
        if (empty($pending)) {
            return;
        }

        $lockStmt = $pdo->query("SELECT GET_LOCK('" . self::LOCK_NAME . "', 3)");
        $lockAcquired = (bool) $lockStmt->fetchColumn();
        $lockStmt->closeCursor();

        if (!$lockAcquired) {
            // Another request is already migrating — proceed without blocking this one.
            return;
        }

        try {
            // Re-check now that we hold the lock: another worker may have just finished.
            foreach (self::pendingMigrations($pdo) as $file => $path) {
                self::applyMigration($pdo, $file, $path);
            }
        } finally {
            $releaseStmt = $pdo->query("SELECT RELEASE_LOCK('" . self::LOCK_NAME . "')");
            $releaseStmt->closeCursor();
        }
    }

    /** @return array<string, string> migration filename => full path, in order */
    private static function pendingMigrations(PDO $pdo): array
    {
        $dir = dirname(__DIR__, 2) . '/database/migrations';
        $files = array_merge(glob($dir . '/*.sql') ?: [], glob($dir . '/*.php') ?: []);
        sort($files);

        $appliedStmt = $pdo->query('SELECT migration FROM schema_migrations');
        $applied = array_column($appliedStmt->fetchAll(), 'migration');
        $appliedStmt->closeCursor();

        $pending = [];
        foreach ($files as $path) {
            $name = basename($path);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $firstLine = '';
            $handle = fopen($path, 'r');
            if ($handle) {
                $firstLine = fgets($handle) ?: '';
                fclose($handle);
            }
            if (str_contains($firstLine, '@manual-only')) {
                continue;
            }
            $pending[$name] = $path;
        }

        return $pending;
    }

    private static function applyMigration(PDO $pdo, string $name, string $path): void
    {
        try {
            if (str_ends_with($name, '.php')) {
                self::applyPhpMigration($pdo, $path);
            } else {
                self::applySqlMigration($pdo, $name, $path);
            }
        } catch (\Throwable $e) {
            error_log("EventPlanner Migrator: échec de {$name} : " . $e->getMessage());
            return;
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
        $stmt->execute([$name]);
    }

    private static function applySqlMigration(PDO $pdo, string $name, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            return;
        }

        foreach (self::splitStatements($sql) as $statement) {
            if ($statement === '') {
                continue;
            }
            try {
                $pdo->exec($statement);
            } catch (\PDOException $e) {
                throw new \RuntimeException("échec sur l'instruction : {$statement}\n" . $e->getMessage(), 0, $e);
            }
        }
    }

    /** Runs a plain PHP migration script with `$pdo` in scope — see the class docblock. */
    private static function applyPhpMigration(PDO $pdo, string $path): void
    {
        (function (PDO $pdo, string $__migrationPath) {
            require $__migrationPath;
        })($pdo, $path);
    }

    /** Naive but sufficient splitter for this project's migration files: no stored procedures, no semicolons inside string literals. */
    private static function splitStatements(string $sql): array
    {
        $lines = explode("\n", $sql);
        $statements = [];
        $buffer = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with($trimmed, ';')) {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
