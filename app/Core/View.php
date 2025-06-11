<?php

namespace App\Core;

class View
{
    private string $basePath;
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;
    private array $data = [];
    private static ?View $instance = null;
    
    public function __construct()
    {
        $this->basePath = APP_PATH . '/Views';
        self::$instance = $this;
    }
    
    public static function getInstance(): View
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        
        // Extract data to make it available in view
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewPath = $this->getViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $view");
        }
        
        include $viewPath;
        
        $content = ob_get_clean();
        
        // If a layout is set, render within layout
        if ($this->layout !== null) {
            $layoutPath = $this->getViewPath($this->layout);
            
            if (!file_exists($layoutPath)) {
                throw new \Exception("Layout not found: {$this->layout}");
            }
            
            // Make content available in layout
            $this->sections['content'] = $content;
            extract($this->data);
            
            ob_start();
            include $layoutPath;
            $content = ob_get_clean();
            
            // Reset layout
            $this->layout = null;
        }
        
        // Reset sections
        $this->sections = [];
        
        return $content;
    }
    
    public function partial(string $partial, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data);
        
        $partialPath = $this->getViewPath($partial);
        
        if (!file_exists($partialPath)) {
            throw new \Exception("Partial not found: $partial");
        }
        
        include $partialPath;
    }
    
    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }
    
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }
    
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \Exception("No section started");
        }
        
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }
    
    public function yield(string $section, string $default = ''): void
    {
        echo $this->sections[$section] ?? $default;
    }
    
    public function hasSection(string $section): bool
    {
        return isset($this->sections[$section]);
    }
    
    private function getViewPath(string $view): string
    {
        // Check if it's a module view (e.g., "Articles::index")
        if (strpos($view, '::') !== false) {
            [$module, $viewName] = explode('::', $view);
            return APP_PATH . "/Modules/$module/Views/$viewName.php";
        }
        
        // Regular view
        return $this->basePath . '/' . str_replace('.', '/', $view) . '.php';
    }
    
    // Helper methods
    
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    public function e(string $string): string
    {
        return $this->escape($string);
    }
    
    public function url(string $path = ''): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
    
    public function asset(string $path): string
    {
        return $this->url('assets/' . ltrim($path, '/'));
    }
    
    public function csrf(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
    
    public function method(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
    
    public function old(string $field, string $default = ''): string
    {
        return $_SESSION['old_input'][$field] ?? $default;
    }
    
    public function error(string $field): ?string
    {
        return $_SESSION['errors'][$field][0] ?? null;
    }
    
    public function hasError(string $field): bool
    {
        return isset($_SESSION['errors'][$field]);
    }
    
    public function errors(): array
    {
        return $_SESSION['errors'] ?? [];
    }
    
    public function auth(): ?array
    {
        return Auth::user();
    }
    
    public function can(string $permission): bool
    {
        return Auth::check();
    }
}