<?php

namespace App\Core;

class Cache
{
    private static ?Cache $instance = null;
    private string $cachePath;
    private string $prefix;
    
    private function __construct()
    {
        $this->cachePath = STORAGE_PATH . '/cache';
        $this->prefix = $_ENV['CACHE_PREFIX'] ?? 'cache_';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get(string $key, $default = null)
    {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        // Check if expired
        if ($data['expires'] !== null && $data['expires'] < time()) {
            $this->forget($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    public function put(string $key, $value, ?int $seconds = null): bool
    {
        $filename = $this->getCacheFilename($key);
        $expires = $seconds !== null ? time() + $seconds : null;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        return file_put_contents($filename, serialize($data)) !== false;
    }
    
    public function forever(string $key, $value): bool
    {
        return $this->put($key, $value, null);
    }
    
    public function forget(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    public function flush(): bool
    {
        $files = glob($this->cachePath . '/' . $this->prefix . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    public function remember(string $key, int $seconds, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->put($key, $value, $seconds);
        
        return $value;
    }
    
    public function rememberForever(string $key, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->forever($key, $value);
        
        return $value;
    }
    
    private function getCacheFilename(string $key): string
    {
        return $this->cachePath . '/' . $this->prefix . md5($key);
    }
    
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->forever($key, $new);
        return $new;
    }
    
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }
    
    public function clean(): void
    {
        $files = glob($this->cachePath . '/' . $this->prefix . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(file_get_contents($file));
                
                if ($data['expires'] !== null && $data['expires'] < time()) {
                    unlink($file);
                }
            }
        }
    }
}