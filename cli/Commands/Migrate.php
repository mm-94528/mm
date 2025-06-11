<?php

namespace App\Cli\Commands;

use App\Core\Database;

class Migrate extends \Command
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function getName(): string
    {
        return 'migrate';
    }
    
    public function getDescription(): string
    {
        return 'Run database migrations';
    }
    
    public function execute(array $args): void
    {
        if ($this->hasOption($args, 'help')) {
            $this->showHelp();
            return;
        }
        
        $this->info("Running migrations...\n");
        
        // Create migrations table if not exists
        $this->createMigrationsTable();
        
        // Get migration files
        $migrations = $this->getMigrationFiles();
        
        if (empty($migrations)) {
            $this->warning("No migrations found.");
            return;
        }
        
        // Get already run migrations
        $ranMigrations = $this->getRanMigrations();
        
        $pendingMigrations = array_diff($migrations, $ranMigrations);
        
        if (empty($pendingMigrations)) {
            $this->info("Nothing to migrate.");
            return;
        }
        
        // Run pending migrations
        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
        }
        
        $this->info("\nMigrations completed successfully!");
    }
    
    private function showHelp(): void
    {
        $this->line("Usage: migrate [options]");
        $this->line("\nRun all pending database migrations.");
        $this->line("\nOptions:");
        $this->line("  --rollback    Rollback the last batch of migrations");
        $this->line("  --fresh       Drop all tables and re-run all migrations");
    }
    
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->query($sql);
    }
    
    private function getMigrationFiles(): array
    {
        $files = [];
        
        // Core migrations
        $coreMigrations = glob(ROOT_PATH . '/database/migrations/*.sql');
        $files = array_merge($files, $coreMigrations);
        
        // Module migrations
        $modules = glob(APP_PATH . '/Modules/*/Database/Migrations/*.sql');
        $files = array_merge($files, $modules);
        
        // Get only filenames
        return array_map('basename', $files);
    }
    
    private function getRanMigrations(): array
    {
        $results = $this->db->select("SELECT migration FROM migrations");
        return array_column($results, 'migration');
    }
    
    private function runMigration(string $filename): void
    {
        $this->line("Migrating: {$filename}");
        
        // Find the file
        $filepath = $this->findMigrationFile($filename);
        
        if (!$filepath) {
            $this->error("Migration file not found: {$filename}");
            return;
        }
        
        try {
            // Read and execute SQL
            $sql = file_get_contents($filepath);
            
            // Split by semicolon to handle multiple statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->query($statement);
                }
            }
            
            // Record migration
            $batch = $this->getNextBatch();
            $this->db->insert('migrations', [
                'migration' => $filename,
                'batch' => $batch
            ]);
            
            $this->info("Migrated:  {$filename}");
        } catch (\Exception $e) {
            $this->error("Failed to migrate {$filename}: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function findMigrationFile(string $filename): ?string
    {
        // Check core migrations
        $corePath = ROOT_PATH . '/database/migrations/' . $filename;
        if (file_exists($corePath)) {
            return $corePath;
        }
        
        // Check module migrations
        $modules = glob(APP_PATH . '/Modules/*/Database/Migrations/' . $filename);
        if (!empty($modules)) {
            return $modules[0];
        }
        
        return null;
    }
    
    private function getNextBatch(): int
    {
        $result = $this->db->selectOne("SELECT MAX(batch) as max_batch FROM migrations");
        return ($result['max_batch'] ?? 0) + 1;
    }
}