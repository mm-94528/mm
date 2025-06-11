<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static ?Database $instance = null;
    
    private function __construct()
    {
        $this->connect();
    }
    
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $_ENV['DB_CONNECTION'],
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_DATABASE']
                );
                
                self::$connection = new PDO(
                    $dsn,
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \Exception("Connessione al database fallita: " . $e->getMessage());
            }
        }
    }
    
    public function getConnection(): PDO
    {
        return self::$connection;
    }
    
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::$connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function selectOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }
    
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $values = array_map(fn($col) => ":$col", $columns);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $values)
        );
        
        $this->query($sql, $data);
        return (int) self::$connection->lastInsertId();
    }
    
    public function update(string $table, array $data, array $where): int
    {
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "$column = :set_$column";
        }
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = :where_$column";
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );
        
        $params = [];
        foreach ($data as $column => $value) {
            $params["set_$column"] = $value;
        }
        foreach ($where as $column => $value) {
            $params["where_$column"] = $value;
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete(string $table, array $where): int
    {
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = :$column";
        }
        
        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereClause)
        );
        
        $stmt = $this->query($sql, $where);
        return $stmt->rowCount();
    }
    
    public function beginTransaction(): void
    {
        self::$connection->beginTransaction();
    }
    
    public function commit(): void
    {
        self::$connection->commit();
    }
    
    public function rollback(): void
    {
        self::$connection->rollBack();
    }
    
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }
}

class QueryBuilder
{
    private Database $db;
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $selects = ['*'];
    
    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }
    
    public function select(...$columns): self
    {
        $this->selects = empty($columns) ? ['*'] : $columns;
        return $this;
    }
    
    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->generatePlaceholder($column);
        $this->wheres[] = "$column $operator :$placeholder";
        $this->bindings[$placeholder] = $value;
        
        return $this;
    }
    
    public function whereIn(string $column, array $values): self
    {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = $this->generatePlaceholder($column . '_' . $i);
            $placeholders[] = ":$placeholder";
            $this->bindings[$placeholder] = $value;
        }
        
        $this->wheres[] = "$column IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }
    
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "JOIN $table ON $first $operator $second";
        return $this;
    }
    
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN $table ON $first $operator $second";
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->db->select($sql, $this->bindings);
    }
    
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    public function count(): int
    {
        $this->selects = ['COUNT(*) as count'];
        $result = $this->first();
        return (int) ($result['count'] ?? 0);
    }
    
    public function exists(): bool
    {
        return $this->count() > 0;
    }
    
    public function insert(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }
    
    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            throw new \Exception("UPDATE senza WHERE clause è pericoloso!");
        }
        
        $setClause = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $placeholder = $this->generatePlaceholder('set_' . $column);
            $setClause[] = "$column = :$placeholder";
            $params[$placeholder] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause);
        $sql .= " WHERE " . implode(' AND ', $this->wheres);
        
        $params = array_merge($params, $this->bindings);
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \Exception("DELETE senza WHERE clause è pericoloso!");
        }
        
        $sql = "DELETE FROM {$this->table}";
        $sql .= " WHERE " . implode(' AND ', $this->wheres);
        
        $stmt = $this->db->query($sql, $this->bindings);
        return $stmt->rowCount();
    }
    
    private function buildSelectQuery(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects);
        $sql .= " FROM {$this->table}";
        
        foreach ($this->joins as $join) {
            $sql .= " $join";
        }
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return $sql;
    }
    
    private function generatePlaceholder(string $base): string
    {
        return str_replace('.', '_', $base) . '_' . uniqid();
    }
}