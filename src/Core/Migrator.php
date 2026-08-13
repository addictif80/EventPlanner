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
 * on its first line and this runner will skip it, leaving it for a deliberate
 * `mysql < ...` run by the operator.
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
        $files = glob($dir . '/*.sql') ?: [];
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
                error_log("EventPlanner Migrator: échec de {$name} sur l'instruction : {$statement}\n" . $e->getMessage());
                return;
            }
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
        $stmt->execute([$name]);
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
