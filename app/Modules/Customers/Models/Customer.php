<?php

namespace App\Modules\Customers\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';
    
    protected array $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'vat_number',
        'notes',
        'active'
    ];
    
    protected array $casts = [
        'active' => 'boolean'
    ];
    
    public function isActive(): bool
    {
        return $this->active === true;
    }
    
    public static function generateCode(): string
    {
        $lastCustomer = static::orderBy('id', 'DESC')->first();
        $lastId = $lastCustomer ? $lastCustomer->id : 0;
        
        return 'CUS' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
    
    public static function active()
    {
        return static::where('active', '=', 1);
    }
}