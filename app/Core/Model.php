<?php

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected Database $db;
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->fill($attributes);
        $this->original = $this->attributes;
    }
    
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }
    
    protected function isFillable(string $key): bool
    {
        if (empty($this->fillable)) {
            return true;
        }
        return in_array($key, $this->fillable);
    }
    
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $this->castAttribute($key, $value);
    }
    
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
    
    protected function castAttribute(string $key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'string':
                return (string) $value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : (array) $value;
            case 'json':
                return is_string($value) ? json_decode($value) : $value;
            case 'datetime':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            default:
                return $value;
        }
    }
    
    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        }
        return $this->insert();
    }
    
    protected function insert(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->setAttribute('created_at', $now);
            $this->setAttribute('updated_at', $now);
        }
        
        $id = $this->db->insert($this->table, $this->attributes);
        
        if ($id) {
            $this->setAttribute($this->primaryKey, $id);
            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }
        
        return false;
    }
    
    protected function update(): bool
    {
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true;
        }
        
        if ($this->timestamps) {
            $dirty['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $updated = $this->db->update(
            $this->table,
            $dirty,
            [$this->primaryKey => $this->getAttribute($this->primaryKey)]
        );
        
        if ($updated) {
            $this->original = array_merge($this->original, $dirty);
            return true;
        }
        
        return false;
    }
    
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $deleted = $this->db->delete(
            $this->table,
            [$this->primaryKey => $this->getAttribute($this->primaryKey)]
        );
        
        if ($deleted) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }
    
    public function getDirty(): array
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    public function isDirty(string $key = null): bool
    {
        if ($key === null) {
            return !empty($this->getDirty());
        }
        
        return array_key_exists($key, $this->getDirty());
    }
    
    public static function find($id): ?static
    {
        $instance = new static();
        $result = $instance->db->selectOne(
            "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ?",
            [$id]
        );
        
        if ($result) {
            $instance->fill($result);
            $instance->exists = true;
            $instance->original = $instance->attributes;
            return $instance;
        }
        
        return null;
    }
    
    public static function all(): array
    {
        $instance = new static();
        $results = $instance->db->select("SELECT * FROM {$instance->table}");
        
        return array_map(function ($row) {
            $model = new static($row);
            $model->exists = true;
            $model->original = $model->attributes;
            return $model;
        }, $results);
    }
    
    public static function where(string $column, $operator, $value = null): QueryBuilder
    {
        $instance = new static();
        return $instance->db->table($instance->table)->where($column, $operator, $value);
    }
    
    public static function create(array $attributes): ?static
    {
        $model = new static($attributes);
        
        if ($model->save()) {
            return $model;
        }
        
        return null;
    }
    
    public function toArray(): array
    {
        $array = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    // Relazioni
    
    public function hasOne(string $related, string $foreignKey = null, string $localKey = null): ?Model
    {
        $relatedInstance = new $related();
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return $related::where($foreignKey, '=', $this->getAttribute($localKey))->first();
    }
    
    public function hasMany(string $related, string $foreignKey = null, string $localKey = null): array
    {
        $relatedInstance = new $related();
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        $results = $related::where($foreignKey, '=', $this->getAttribute($localKey))->get();
        
        return array_map(function ($row) use ($related) {
            $model = new $related($row);
            $model->exists = true;
            $model->original = $model->attributes;
            return $model;
        }, $results);
    }
    
    public function belongsTo(string $related, string $foreignKey = null, string $ownerKey = null): ?Model
    {
        $relatedInstance = new $related();
        $foreignKey = $foreignKey ?: $relatedInstance->getForeignKey();
        $ownerKey = $ownerKey ?: $relatedInstance->primaryKey;
        
        return $related::find($this->getAttribute($foreignKey));
    }
    
    protected function getForeignKey(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower($className) . '_id';
    }
    
    // Magic methods
    
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}