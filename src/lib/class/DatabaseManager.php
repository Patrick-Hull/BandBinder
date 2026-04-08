<?php
class DatabaseManager
{
    private PDO $pdo;

    public function __construct()
    {
        // Load .env file if it exists
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($key, $value) = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
        }

        // Get MySQL connection details from environment variables
        $host = getenv('DB_HOST');
        $dbname = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    /**
     * Run a query with parameter binding.
     * Automatically expands arrays for IN (?) clauses.
     */
    public function query(string $sql, array $args = []): array
    {
        // Handle array parameters (e.g., WHERE id IN ?)
        $expandedArgs = [];
        $sql = preg_replace_callback('/\?/', function () use (&$args, &$expandedArgs) {
            $arg = array_shift($args);
            if (is_array($arg)) {
                $placeholders = implode(',', array_fill(0, count($arg), '?'));
                $expandedArgs = array_merge($expandedArgs, $arg);
                return $placeholders;
            } else {
                $expandedArgs[] = $arg;
                return '?';
            }
        }, $sql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($expandedArgs);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exec(string $sql, array $args = []): void
    {
        $this->query($sql, $args);
    }
}