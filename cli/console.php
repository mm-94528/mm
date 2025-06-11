#!/usr/bin/env php
<?php

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CLI_PATH', __DIR__);

// Autoload
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// CLI Application
class Console
{
    private array $commands = [];
    private array $args;
    
    public function __construct($argv)
    {
        $this->args = array_slice($argv, 1);
        $this->loadCommands();
    }
    
    private function loadCommands(): void
    {
        $commandFiles = glob(CLI_PATH . '/Commands/*.php');
        
        foreach ($commandFiles as $file) {
            $className = 'App\\Cli\\Commands\\' . basename($file, '.php');
            
            if (class_exists($className)) {
                $command = new $className();
                $this->commands[$command->getName()] = $command;
            }
        }
    }
    
    public function run(): void
    {
        if (empty($this->args)) {
            $this->showHelp();
            return;
        }
        
        $commandName = $this->args[0];
        
        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return;
        }
        
        if (!isset($this->commands[$commandName])) {
            $this->error("Command '{$commandName}' not found.");
            $this->showHelp();
            return;
        }
        
        $command = $this->commands[$commandName];
        $arguments = array_slice($this->args, 1);
        
        try {
            $command->execute($arguments);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    private function showHelp(): void
    {
        $this->info("Modular Scaffold CLI v1.0.0\n");
        $this->line("Usage: php cli/console.php [command] [arguments]\n");
        $this->line("Available commands:");
        
        foreach ($this->commands as $name => $command) {
            $this->line("  {$name}\t\t{$command->getDescription()}");
        }
        
        $this->line("\nRun 'php cli/console.php [command] --help' for command help.");
    }
    
    public static function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }
    
    public static function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }
    
    public static function warning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }
    
    public static function line(string $message): void
    {
        echo "{$message}\n";
    }
}

// Base Command class
abstract class Command
{
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function execute(array $args): void;
    
    protected function hasOption(array $args, string $option): bool
    {
        return in_array($option, $args) || in_array("--{$option}", $args);
    }
    
    protected function getOption(array $args, string $option): ?string
    {
        foreach ($args as $i => $arg) {
            if ($arg === "--{$option}" && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
        }
        return null;
    }
    
    protected function info(string $message): void
    {
        Console::info($message);
    }
    
    protected function error(string $message): void
    {
        Console::error($message);
    }
    
    protected function warning(string $message): void
    {
        Console::warning($message);
    }
    
    protected function line(string $message): void
    {
        Console::line($message);
    }
    
    protected function ask(string $question): string
    {
        echo "{$question}: ";
        return trim(fgets(STDIN));
    }
    
    protected function confirm(string $question): bool
    {
        $answer = $this->ask("{$question} (y/n)");
        return strtolower($answer) === 'y' || strtolower($answer) === 'yes';
    }
}

// Run console
$console = new Console($argv);
$console->run();