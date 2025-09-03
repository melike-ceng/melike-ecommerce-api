\
    <?php
    namespace App;
    use PDO; use PDOException; use RuntimeException;

    class Database {
        private static ?PDO $pdo = null;

        public static function get(): PDO {
            if (self::$pdo) return self::$pdo;
            $dsn = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=5432';
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('DB bağlanamadı: ' . $e->getMessage());
            }
            self::$pdo = $pdo;
            return $pdo;
        }
    }
