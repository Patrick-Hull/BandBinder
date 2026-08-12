<?php

/**
 * Database Migration Script
 *
 * This script:
 * 1. Creates a migrations tracking table if it doesn't exist
 * 2. Scans src/db/migration/ for .sql files (format: 1-name.sql, 2-name.sql, etc.)
 * 3. Executes only new migrations in numerical order
 * 4. Tracks executed migrations in the migrations table
 *
 * Usage: php src/db/migrate.php
 */

require_once __DIR__ . '/../lib/class/DatabaseManager.php';

class DatabaseMigrator
{
    private DatabaseManager $db;
    private string $migrationDir;

    public function __construct()
    {
        $this->db = new DatabaseManager();
        $this->migrationDir = __DIR__ . '/migration';
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): void
    {
        echo "Starting database migration...\n";

        // Ensure migrations tracking table exists
        $this->createMigrationsTable();

        // Get list of already executed migrations
        $executed = $this->getExecutedMigrations();
        echo "Found " . count($executed) . " previously executed migration(s).\n";

        // Get all migration files
        $migrationFiles = $this->getMigrationFiles();
        echo "Found " . count($migrationFiles) . " total migration file(s).\n";

        // Filter out already executed migrations
        $pending = array_filter($migrationFiles, function($file) use ($executed) {
            return !in_array($file, $executed);
        });

        if (empty($pending)) {
            echo "No pending migrations to execute.\n";
        } else {
            echo "Executing " . count($pending) . " pending migration(s)...\n\n";

            foreach ($pending as $file) {
                $this->executeMigration($file);
            }
        }

        echo "\n✓ Migration completed successfully!\n";
    }

    /**
     * Create the migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
    }

    /**
     * Get list of executed migration filenames
     */
    private function getExecutedMigrations(): array
    {
        $results = $this->db->query("SELECT filename FROM migrations ORDER BY filename");
        return array_column($results, 'filename');
    }

    /**
     * Get all migration files from the migration directory
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0755, true);
            echo "Created migration directory: {$this->migrationDir}\n";
            return [];
        }

        $files = glob($this->migrationDir . '/*.sql');

        // Sort files numerically by their leading number
        usort($files, function($a, $b) {
            $numA = $this->extractMigrationNumber(basename($a));
            $numB = $this->extractMigrationNumber(basename($b));
            return $numA <=> $numB;
        });

        return array_map('basename', $files);
    }

    /**
     * Extract the leading number from a migration filename
     */
    private function extractMigrationNumber(string $filename): int
    {
        if (preg_match('/^(\d+)-/', $filename, $matches)) {
            return (int)$matches[1];
        }
        return PHP_INT_MAX; // Put files without numbers at the end
    }

    /**
     * Execute a single migration file
     */
    private function executeMigration(string $filename): void
    {
        $filepath = $this->migrationDir . '/' . $filename;

        echo "→ Executing: {$filename}...";

        $sql = file_get_contents($filepath);

        if (empty(trim($sql))) {
            echo " [SKIPPED - Empty file]\n";
            return;
        }

        // Split by semicolon to handle multiple statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );

        // MySQL error codes that mean "already exists" — safe to skip on re-run
        $ignorableCodes = [
            1060, // Duplicate column name (ADD COLUMN on existing column)
            1061, // Duplicate key name (ADD INDEX on existing index)
            1050, // Table already exists (CREATE TABLE without IF NOT EXISTS)
        ];

        try {
            foreach ($statements as $statement) {
                try {
                    $this->db->exec($statement);
                } catch (Exception $e) {
                    $code = (int)$e->getCode();
                    // PDO wraps the driver error; extract MySQL error number from message if needed
                    if (!in_array($code, $ignorableCodes)) {
                        preg_match('/\b(\d{4})\b/', $e->getMessage(), $m);
                        $code = isset($m[1]) ? (int)$m[1] : $code;
                    }
                    if (in_array($code, $ignorableCodes)) {
                        echo "\n  [SKIP] Already applied: " . trim(substr($statement, 0, 80)) . "...";
                    } else {
                        throw $e;
                    }
                }
            }

            // Record migration as executed
            $this->db->exec(
                "INSERT INTO migrations (filename) VALUES (?)",
                [$filename]
            );

            echo " [SUCCESS]\n";
        } catch (Exception $e) {
            echo " [FAILED]\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

}

// Run the migration
try {
    $migrator = new DatabaseMigrator();
    $migrator->migrate();
    exit(0);
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
