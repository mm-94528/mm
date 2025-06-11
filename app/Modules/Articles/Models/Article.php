<?php

namespace App\Modules\Articles\Models;

use App\Core\Model;

class Article extends Model
{
    protected string $table = 'articles';
    
    protected array $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
        'views'
    ];
    
    protected array $casts = [
        'views' => 'integer',
        'published_at' => 'datetime'
    ];
    
    public function user()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'user_id');
    }
    
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at !== null && 
               $this->published_at <= new \DateTime();
    }
    
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
    
    public static function published()
    {
        return static::where('status', '=', 'published')
                     ->where('published_at', '<=', date('Y-m-d H:i:s'));
    }
}