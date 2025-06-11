<?php

namespace App\Core;

class Module
{
    private string $name;
    private array $config;
    private bool $enabled;
    private string $path;
    
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
        $this->path = APP_PATH . '/Modules/' . $name;
        $this->enabled = $this->checkIfEnabled();
    }
    
    private function checkIfEnabled(): bool
    {
        $modulesConfig = require CONFIG_PATH . '/modules.php';
        return in_array($this->name, $modulesConfig['enabled']);
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
    
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    public function enable(): bool
    {
        if ($this->enabled) {
            return true;
        }
        
        // Update modules config
        $configFile = CONFIG_PATH . '/modules.php';
        $config = require $configFile;
        
        if (!in_array($this->name, $config['enabled'])) {
            $config['enabled'][] = $this->name;
            
            $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($configFile, $content);
            
            $this->enabled = true;
            
            // Run module installation if needed
            $this->install();
        }
        
        return true;
    }
    
    public function disable(): bool
    {
        if (!$this->enabled) {
            return true;
        }
        
        // Prevent disabling core modules
        if ($this->isCoreModule()) {
            return false;
        }
        
        // Update modules config
        $configFile = CONFIG_PATH . '/modules.php';
        $config = require $configFile;
        
        $config['enabled'] = array_values(array_diff($config['enabled'], [$this->name]));
        
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($configFile, $content);
        
        $this->enabled = false;
        
        return true;
    }
    
    public function isCoreModule(): bool
    {
        return in_array($this->name, ['Admin', 'Auth']);
    }
    
    public function loadRoutes(Router $router): void
    {
        $routesFile = $this->path . '/routes.php';
        
        if (file_exists($routesFile)) {
            // Make router available in routes file
            $module = $this;
            require $routesFile;
        }
    }
    
    public function install(): void
    {
        // Run migrations
        $this->runMigrations();
        
        // Run seeders if any
        $this->runSeeders();
        
        // Copy assets if any
        $this->copyAssets();
    }
    
    private function runMigrations(): void
    {
        $migrationsPath = $this->path . '/Database/Migrations';
        
        if (!is_dir($migrationsPath)) {
            return;
        }
        
        $files = glob($migrationsPath . '/*.php');
        $db = Database::getInstance();
        
        foreach ($files as $file) {
            $migration = require $file;
            
            if (is_callable($migration)) {
                $migration($db);
            }
        }
    }
    
    private function runSeeders(): void
    {
        $seedersPath = $this->path . '/Database/Seeders';
        
        if (!is_dir($seedersPath)) {
            return;
        }
        
        $files = glob($seedersPath . '/*.php');
        $db = Database::getInstance();
        
        foreach ($files as $file) {
            $seeder = require $file;
            
            if (is_callable($seeder)) {
                $seeder($db);
            }
        }
    }
    
    private function copyAssets(): void
    {
        $assetsPath = $this->path . '/Assets';
        $publicPath = PUBLIC_PATH . '/assets/modules/' . strtolower($this->name);
        
        if (!is_dir($assetsPath)) {
            return;
        }
        
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }
        
        $this->copyDirectory($assetsPath, $publicPath);
    }
    
    private function copyDirectory(string $source, string $dest): void
    {
        $dir = opendir($source);
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcFile = $source . '/' . $file;
            $destFile = $dest . '/' . $file;
            
            if (is_dir($srcFile)) {
                if (!is_dir($destFile)) {
                    mkdir($destFile, 0755, true);
                }
                $this->copyDirectory($srcFile, $destFile);
            } else {
                copy($srcFile, $destFile);
            }
        }
        
        closedir($dir);
    }
    
    public function getVersion(): string
    {
        return $this->config['version'] ?? '1.0.0';
    }
    
    public function getAuthor(): string
    {
        return $this->config['author'] ?? 'Unknown';
    }
    
    public function getDescription(): string
    {
        return $this->config['description'] ?? '';
    }
    
    public function getDependencies(): array
    {
        return $this->config['dependencies'] ?? [];
    }
    
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->config['permissions'] ?? [];
        return in_array($permission, $permissions);
    }
}