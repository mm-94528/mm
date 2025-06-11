<?php

namespace App\Core;

use App\Helpers\Response;

abstract class Controller
{
    protected View $view;
    protected array $middleware = [];
    
    public function __construct()
    {
        $this->view = new View();
    }
    
    protected function view(string $view, array $data = []): string
    {
        return $this->view->render($view, $data);
    }
    
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
    
    protected function redirect(string $url, int $status = 302): void
    {
        header("Location: $url", true, $status);
        exit;
    }
    
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }
    
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $fieldRules = explode('|', $ruleSet);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                $error = $this->validateRule($field, $value, $ruleName, $ruleParam);
                
                if ($error) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    protected function validateRule(string $field, $value, string $rule, $param = null): ?string
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return "Il campo $field è obbligatorio";
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "Il campo $field deve essere un'email valida";
                }
                break;
                
            case 'min':
                if ($value && strlen($value) < $param) {
                    return "Il campo $field deve essere almeno $param caratteri";
                }
                break;
                
            case 'max':
                if ($value && strlen($value) > $param) {
                    return "Il campo $field non può superare $param caratteri";
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "Il campo $field deve essere numerico";
                }
                break;
                
            case 'unique':
                [$table, $column] = explode(',', $param);
                $db = Database::getInstance();
                $exists = $db->table($table)->where($column, '=', $value)->exists();
                if ($exists) {
                    return "Il valore del campo $field è già in uso";
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($data[$confirmField] ?? null)) {
                    return "La conferma del campo $field non corrisponde";
                }
                break;
        }
        
        return null;
    }
    
    protected function authorize(string $permission): bool
    {
        // Implementazione base, da estendere con sistema di permessi
        return Auth::check();
    }
    
    protected function getUserInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }
        
        return array_merge($_GET, $input);
    }
    
    protected function getUploadedFile(string $name): ?array
    {
        if (!isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        return $_FILES[$name];
    }
    
    protected function uploadFile(array $file, string $directory = 'uploads'): ?string
    {
        $uploadDir = PUBLIC_PATH . '/' . $directory;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $directory . '/' . $filename;
        }
        
        return null;
    }
    
    protected function success($data = null, string $message = 'Operazione completata con successo'): Response
    {
        return Response::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    protected function error(string $message = 'Si è verificato un errore', int $status = 400, $errors = null): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    
    protected function paginate($query, int $perPage = 15): array
    {
        $page = (int) ($_GET['page'] ?? 1);
        $page = max(1, $page);
        
        $total = $query->count();
        $lastPage = (int) ceil($total / $perPage);
        
        $items = $query
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();
        
        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ]
        ];
    }
    
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}