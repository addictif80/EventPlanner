<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /**
     * Whether this table is tenant-scoped (has an organization_id column that
     * must be filtered on every read/write). True for virtually every business
     * table; set to false only for tables with no organization_id (e.g. the
     * organizations table itself).
     */
    protected static bool $scoped = true;

    public static function all(string $orderBy = 'id DESC'): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        $params = [];

        if (static::$scoped) {
            $sql .= ' WHERE organization_id = ?';
            $params[] = Auth::organizationId();
        }

        $sql .= ' ORDER BY ' . $orderBy;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?';
        $params = [$id];

        if (static::$scoped) {
            $sql .= ' AND organization_id = ?';
            $params[] = Auth::organizationId();
        }

        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        if (static::$scoped && !array_key_exists('organization_id', $data)) {
            $data['organization_id'] = Auth::organizationId();
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = sprintf('UPDATE %s SET %s WHERE %s = :__id', static::$table, $assignments, static::$primaryKey);

        $data['__id'] = $id;

        if (static::$scoped) {
            $sql .= ' AND organization_id = :__org_id';
            $data['__org_id'] = Auth::organizationId();
        }

        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        $sql = 'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?';
        $params = [$id];

        if (static::$scoped) {
            $sql .= ' AND organization_id = ?';
            $params[] = Auth::organizationId();
        }

        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function where(string $column, $value): array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . $column . ' = ?';
        $params = [$value];

        if (static::$scoped) {
            $sql .= ' AND organization_id = ?';
            $params[] = Auth::organizationId();
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . static::$table;
        $params = [];

        if (static::$scoped) {
            $sql .= ' WHERE organization_id = ?';
            $params[] = Auth::organizationId();
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
