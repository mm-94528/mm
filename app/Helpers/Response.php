<?php

namespace App\Helpers;

class Response
{
    private $content;
    private int $status;
    private array $headers;
    
    public function __construct($content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }
    
    public static function json($data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        
        return new self(json_encode($data), $status, $headers);
    }
    
    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        
        return new self($content, $status, $headers);
    }
    
    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }
    
    public static function download(string $file, string $name = null): self
    {
        if (!file_exists($file)) {
            throw new \Exception("File not found: $file");
        }
        
        $name = $name ?: basename($file);
        $headers = [
            'Content-Type' => mime_content_type($file),
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Content-Length' => filesize($file)
        ];
        
        return new self(file_get_contents($file), 200, $headers);
    }
    
    public function send(): void
    {
        // Send status code
        http_response_code($this->status);
        
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        // Send content
        echo $this->content;
    }
    
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    public function getContent()
    {
        return $this->content;
    }
    
    public function getStatus(): int
    {
        return $this->status;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
}