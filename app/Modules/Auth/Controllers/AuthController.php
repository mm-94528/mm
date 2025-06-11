<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Modules\Auth\Models\User;
use PHPMailer\PHPMailer\PHPMailer;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        
        return view('Auth::login');
    }
    
    public function login()
    {
        $input = $this->getUserInput();
        
        // Validate input
        $errors = $this->validate($input, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Correggi gli errori nel form', 422, $errors);
        }
        
        // Attempt login
        if (Auth::attempt([
            'email' => $input['email'],
            'password' => $input['password']
        ], $input['remember'] ?? false)) {
            $intendedUrl = $_SESSION['intended_url'] ?? '/dashboard';
            unset($_SESSION['intended_url']);
            
            return $this->success(null, 'Accesso effettuato con successo')
                ->withHeader('X-Redirect', $intendedUrl);
        }
        
        return $this->error('Credenziali non valide', 401);
    }
    
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        
        return view('Auth::register');
    }
    
    public function register()
    {
        $input = $this->getUserInput();
        
        // Validate input
        $errors = $this->validate($input, [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Correggi gli errori nel form', 422, $errors);
        }
        
        // Create user
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => password_hash($input['password'], PASSWORD_DEFAULT),
            'role' => 'user'
        ]);
        
        if ($user) {
            // Auto login
            Auth::login($user->toArray());
            
            return $this->success(null, 'Registrazione completata con successo')
                ->withHeader('X-Redirect', '/dashboard');
        }
        
        return $this->error('Errore durante la registrazione');
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
    
    public function dashboard()
    {
        $stats = [
            'total_articles' => \App\Modules\Articles\Models\Article::count(),
            'total_customers' => \App\Modules\Customers\Models\Customer::count(),
            'recent_articles' => \App\Modules\Articles\Models\Article::where('user_id', Auth::id())
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
        ];
        
        return view('Auth::dashboard', compact('stats'));
    }
    
    public function profile()
    {
        $user = User::find(Auth::id());
        return view('Auth::profile', compact('user'));
    }
    
    public function updateProfile()
    {
        $input = $this->getUserInput();
        $userId = Auth::id();
        
        // Validate input
        $errors = $this->validate($input, [
            'name' => 'required|min:3|max:255',
            'email' => "required|email|unique:users,email,{$userId}"
        ]);
        
        if (!empty($errors)) {
            return $this->error('Correggi gli errori nel form', 422, $errors);
        }
        
        // Update user
        $user = User::find($userId);
        $user->name = $input['name'];
        $user->email = $input['email'];
        
        if ($user->save()) {
            // Update session
            $_SESSION['user']['name'] = $user->name;
            $_SESSION['user']['email'] = $user->email;
            
            return $this->success(null, 'Profilo aggiornato con successo');
        }
        
        return $this->error('Errore durante l\'aggiornamento');
    }
    
    public function updatePassword()
    {
        $input = $this->getUserInput();
        
        // Validate input
        $errors = $this->validate($input, [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Correggi gli errori nel form', 422, $errors);
        }
        
        // Verify current password
        $user = User::find(Auth::id());
        if (!password_verify($input['current_password'], $user->password)) {
            return $this->error('Password attuale non corretta', 422, [
                'current_password' => ['Password non corretta']
            ]);
        }
        
        // Update password
        $user->password = password_hash($input['password'], PASSWORD_DEFAULT);
        
        if ($user->save()) {
            return $this->success(null, 'Password aggiornata con successo');
        }
        
        return $this->error('Errore durante l\'aggiornamento della password');
    }
    
    public function showResetForm()
    {
        return view('Auth::password.email');
    }
    
    public function sendResetEmail()
    {
        $input = $this->getUserInput();
        
        // Validate input
        $errors = $this->validate($input, [
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            return $this->error('Inserisci un\'email valida', 422, $errors);
        }
        
        // Find user
        $user = User::where('email', $input['email'])->first();
        
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            
            // Store token
            $db = \App\Core\Database::getInstance();
            $db->insert('password_resets', [
                'email' => $user->email,
                'token' => hash('sha256', $token)
            ]);
            
            // Send email
            $this->sendPasswordResetEmail($user->email, $token);
        }
        
        // Always show success to prevent email enumeration
        return $this->success(null, 'Se l\'email esiste, riceverai le istruzioni per il reset');
    }
    
    private function sendPasswordResetEmail($email, $token)
    {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = $_ENV['MAIL_PORT'];
            
            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body = view('Auth::emails.reset-password', [
                'url' => url("/password/reset/{$token}")
            ]);
            
            $mail->send();
        } catch (\Exception $e) {
            // Log error
        }
    }
}