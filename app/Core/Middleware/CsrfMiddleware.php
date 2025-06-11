<?php

namespace App\Core\Middleware;

use App\Helpers\Response;

class CsrfMiddleware
{
    private array $excludedMethods = ['GET', 'HEAD', 'OPTIONS'];
    
    public function handle(): ?Response
    {
        if (in_array($_SERVER['REQUEST_METHOD'], $this->excludedMethods)) {
            return null;
        }
        
        $token = $this->getTokenFromRequest();
        
        if (!$token || !$this->tokensMatch($token)) {
            if ($this->isAjaxRequest()) {
                return Response::json(['error' => 'CSRF token mismatch'], 419);
            }
            
            http_response_code(419);
            die('CSRF token mismatch');
        }
        
        return null;
    }
    
    private function getTokenFromRequest(): ?string
    {
        return $_POST['csrf_token'] ?? 
               $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
               null;
    }
    
    private function tokensMatch(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if (!$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}