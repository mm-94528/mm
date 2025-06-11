<?php

namespace App\Core;

use App\Core\Router;
use App\Core\Database;
use App\Core\Module;
use App\Helpers\Response;

class Application
{
    private static ?Application $instance = null;
    private Router $router;
    private Database $database;
    private array $modules = [];
    
    public function __construct()
    {
        self::$instance = $this;
        
        // Initialize core components
        $this->database = new Database();
        $this->router = new Router();
        
        // Load modules
        $this->loadModules();
        
        // Load routes
        $this->loadRoutes();
    }
    
    public static function getInstance(): ?Application
    {
        return self::$instance;
    }
    
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    public function getDatabase(): Database
    {
        return $this->database;
    }
    
    private function loadModules(): void
    {
        $modulesConfig = require CONFIG_PATH . '/modules.php';
        $modulesPath = APP_PATH . '/Modules';
        
        foreach ($modulesConfig['enabled'] as $moduleName) {
            $moduleConfigFile = $modulesPath . '/' . $moduleName . '/module.json';
            
            if (file_exists($moduleConfigFile)) {
                $moduleConfig = json_decode(file_get_contents($moduleConfigFile), true);
                $this->modules[$moduleName] = new Module($moduleName, $moduleConfig);
            }
        }
    }
    
    private function loadRoutes(): void
    {
        // Load core routes
        require APP_PATH . '/routes.php';
        
        // Load module routes
        foreach ($this->modules as $module) {
            if ($module->isEnabled()) {
                $module->loadRoutes($this->router);
            }
        }
    }
    
    public function run(): void
    {
        try {
            $response = $this->router->dispatch();
            
            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    private function handleException(\Exception $e): void
    {
        if ($_ENV['APP_DEBUG'] === 'true') {
            $response = [
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        } else {
            $response = [
                'error' => true,
                'message' => 'Si è verificato un errore. Riprova più tardi.'
            ];
        }
        
        if ($this->isAjaxRequest()) {
            Response::json($response, 500);
        } else {
            http_response_code(500);
            include APP_PATH . '/Views/errors/500.php';
        }
    }
    
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getModule(string $name): ?Module
    {
        return $this->modules[$name] ?? null;
    }
    
    public function getModules(): array
    {
        return $this->modules;
    }
}