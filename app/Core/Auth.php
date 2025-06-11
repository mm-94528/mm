<?php

namespace App\Core;

class Auth
{
    private static ?array $user = null;
    
    public static function attempt(array $credentials): bool
    {
        $db = Database::getInstance();
        
        $user = $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND active = 1",
            [$credentials['email']]
        );
        
        if ($user && password_verify($credentials['password'], $user['password'])) {
            self::login($user, $credentials['remember'] ?? false);
            return true;
        }
        
        return false;
    }
    
    public static function login(array $user, bool $remember = false): void
    {
        // Remove sensitive data
        unset($user['password']);
        
        // Store user in session
        $_SESSION['user'] = $user;
        self::$user = $user;
        
        // Set remember me cookie if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $db = Database::getInstance();
            $db->insert('remember_tokens', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', $expires)
            ]);
            
            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', false, true);
        }
        
        // Update last login
        $db = Database::getInstance();
        $db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            ['id' => $user['id']]
        );
    }
    
    public static function logout(): void
    {
        // Clear session
        $_SESSION['user'] = null;
        self::$user = null;
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // Remove from database
            $db = Database::getInstance();
            $db->delete('remember_tokens', [
                'token' => hash('sha256', $token)
            ]);
            
            // Remove cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Destroy session
        session_destroy();
    }
    
    public static function check(): bool
    {
        if (self::$user !== null) {
            return true;
        }
        
        if (isset($_SESSION['user'])) {
            self::$user = $_SESSION['user'];
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return self::loginWithRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    private static function loginWithRememberToken(string $token): bool
    {
        $db = Database::getInstance();
        
        $tokenData = $db->selectOne(
            "SELECT * FROM remember_tokens WHERE token = ? AND expires_at > NOW()",
            [hash('sha256', $token)]
        );
        
        if ($tokenData) {
            $user = $db->selectOne(
                "SELECT * FROM users WHERE id = ? AND active = 1",
                [$tokenData['user_id']]
            );
            
            if ($user) {
                unset($user['password']);
                $_SESSION['user'] = $user;
                self::$user = $user;
                return true;
            }
        }
        
        // Invalid token, remove cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        return false;
    }
    
    public static function user(): ?array
    {
        if (self::check()) {
            return self::$user;
        }
        return null;
    }
    
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }
    
    public static function guest(): bool
    {
        return !self::check();
    }
    
    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user && $user['role'] === $role;
    }
    
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }
    
    public static function can(string $permission): bool
    {
        // Implementazione semplice per ora
        // In futuro si può estendere con un sistema di permessi completo
        if (self::isAdmin()) {
            return true;
        }
        
        // Altri controlli di permessi
        return false;
    }
}