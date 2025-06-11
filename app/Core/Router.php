<?php

namespace App\Core;

use App\Helpers\Response;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $groupStack = [];
    private ?string $currentRoute = null;
    
    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    public function any(string $path, $handler, array $middleware = []): self
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler, $middleware);
        }
        return $this;
    }
    
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }
    
    private function addRoute(string $method, string $path, $handler, array $middleware = []): self
    {
        $path = $this->applyGroupPrefix($path);
        $middleware = $this->mergeGroupMiddleware($middleware);
        
        $pattern = $this->convertToRegex($path);
        
        $this->routes[$method][$pattern] = [
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'params' => $this->extractParams($path)
        ];
        
        return $this;
    }
    
    private function applyGroupPrefix(string $path): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        return $prefix . '/' . trim($path, '/');
    }
    
    private function mergeGroupMiddleware(array $middleware): array
    {
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, (array)$group['middleware']);
            }
        }
        
        return array_merge($groupMiddleware, $middleware);
    }
    
    private function convertToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = preg_replace('/\{([a-zA-Z_]+)\?\}/', '(?P<$1>[^/]*)', $pattern);
        return '#^' . $pattern . '$#';
    }
    
    private function extractParams(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\??}/', $path, $matches);
        return $matches[1];
    }
    
    public function dispatch(): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle method override for PUT/DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        $this->currentRoute = $uri;
        
        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }
        
        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters
                $params = [];
                foreach ($route['params'] as $param) {
                    if (isset($matches[$param])) {
                        $params[$param] = $matches[$param];
                    }
                }
                
                // Execute middleware
                $response = $this->executeMiddleware($route['middleware']);
                if ($response !== null) {
                    return $response;
                }
                
                // Execute handler
                return $this->executeHandler($route['handler'], $params);
            }
        }
        
        return $this->handleNotFound();
    }
    
    private function executeMiddleware(array $middleware): mixed
    {
        foreach ($middleware as $middlewareClass) {
            if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                if (method_exists($middlewareInstance, 'handle')) {
                    $response = $middlewareInstance->handle();
                    if ($response !== null) {
                        return $response;
                    }
                }
            }
        }
        
        return null;
    }
    
    private function executeHandler($handler, array $params): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $params);
                }
            }
        }
        
        throw new \Exception("Handler non valido per la route: {$this->currentRoute}");
    }
    
    private function handleNotFound(): mixed
    {
        http_response_code(404);
        
        if ($this->isAjaxRequest()) {
            return Response::json(['error' => 'Route non trovata'], 404);
        }
        
        return $this->renderErrorPage(404);
    }
    
    private function renderErrorPage(int $code): string
    {
        $errorFile = APP_PATH . "/Views/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            ob_start();
            include $errorFile;
            return ob_get_clean();
        }
        
        return "Errore {$code}";
    }
    
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function url(string $name, array $params = []): string
    {
        // Implementazione per named routes (futuro)
        return $name;
    }
}