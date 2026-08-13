<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static bool $migrationsChecked = false;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $db['host'],
                $db['port'],
                $db['name']
            );

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Erreur de connexion à la base de données. Vérifiez la configuration dans .env');
            }

            // Runs once per process: applies any pending additive migration
            // (see Migrator) so a fresh deploy doesn't 500 until someone SSHes
            // in to run `mysql < migrations/xxx.sql` by hand. Failures here
            // must never take the app down — same failure mode as before this
            // existed (a missing table surfaces where it's actually used).
            if (!self::$migrationsChecked) {
                self::$migrationsChecked = true;
                try {
                    Migrator::runPending(self::$instance);
                } catch (\Throwable $e) {
                    error_log('EventPlanner Migrator: ' . $e->getMessage());
                }
            }
        }

        return self::$instance;
    }
}
