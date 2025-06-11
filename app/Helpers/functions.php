<?php

use App\Core\Application;
use App\Core\View;
use App\Core\Auth;
use App\Core\Cache;
use App\Helpers\Response;

if (!function_exists('app')) {
    function app(): ?Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): string
    {
        return View::getInstance()->render($view, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        header("Location: $url", true, $status);
        exit;
    }
}

if (!function_exists('back')) {
    function back(): void
    {
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('user')) {
    function user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $field, string $default = ''): string
    {
        return $_SESSION['old_input'][$field] ?? $default;
    }
}

if (!function_exists('error')) {
    function error(string $field): ?string
    {
        return $_SESSION['errors'][$field][0] ?? null;
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool
    {
        return isset($_SESSION['errors'][$field]);
    }
}

if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            dump($var);
        }
        die(1);
    }
}

if (!function_exists('dump')) {
    function dump(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        $configFile = CONFIG_PATH . '/' . $file . '.php';
        
        if (!file_exists($configFile)) {
            return $default;
        }
        
        $config = require $configFile;
        
        foreach ($parts as $part) {
            if (isset($config[$part])) {
                $config = $config[$part];
            } else {
                return $default;
            }
        }
        
        return $config;
    }
}

if (!function_exists('cache')) {
    function cache(): Cache
    {
        return Cache::getInstance();
    }
}

if (!function_exists('response')) {
    function response(): Response
    {
        return new Response();
    }
}

if (!function_exists('json')) {
    function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return STORAGE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return PUBLIC_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return APP_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return ROOT_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('slug')) {
    function slug(string $text): string
    {
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim
        $text = trim($text, '-');
        
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        
        // Lowercase
        $text = strtolower($text);
        
        return $text ?: 'n-a';
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 16): string
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
}