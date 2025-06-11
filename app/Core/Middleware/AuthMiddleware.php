<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Helpers\Response;

class AuthMiddleware
{
    public function handle(): ?Response
    {
        if (!Auth::check()) {
            if ($this->isAjaxRequest()) {
                return Response::json(['error' => 'Non autenticato'], 401);
            }
            
            // Store intended URL
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            
            redirect('/login');
        }
        
        return null;
    }
    
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}