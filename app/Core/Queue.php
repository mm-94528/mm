<?php

namespace App\Core;

class Queue
{
    private Database $db;
    private string $table = 'jobs';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function push(string $job, array $data = [], string $queue = 'default', int $delay = 0): int
    {
        $payload = [
            'job' => $job,
            'data' => $data,
            'attempts' => 0
        ];
        
        return $this->db->insert($this->table, [
            'queue' => $queue,
            'payload' => json_encode($payload),
            'available_at' => date('Y-m-d H:i:s', time() + $delay),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function later(int $delay, string $job, array $data = [], string $queue = 'default'): int
    {
        return $this->push($job, $data, $queue, $delay);
    }
    
    public function pop(string $queue = 'default'): ?array
    {
        $this->db->beginTransaction();
        
        try {
            // Get next available job
            $job = $this->db->selectOne(
                "SELECT * FROM {$this->table} 
                WHERE queue = ? 
                AND available_at <= NOW() 
                AND (reserved_at IS NULL OR reserved_at < DATE_SUB(NOW(), INTERVAL 1 HOUR))
                ORDER BY id ASC 
                LIMIT 1 
                FOR UPDATE",
                [$queue]
            );
            
            if (!$job) {
                $this->db->commit();
                return null;
            }
            
            // Reserve the job
            $this->db->update($this->table, [
                'reserved_at' => date('Y-m-d H:i:s'),
                'attempts' => $job['attempts'] + 1
            ], ['id' => $job['id']]);
            
            $this->db->commit();
            
            return $job;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function delete(int $id): void
    {
        $this->db->delete($this->table, ['id' => $id]);
    }
    
    public function release(int $id, int $delay = 0): void
    {
        $this->db->update($this->table, [
            'reserved_at' => null,
            'available_at' => date('Y-m-d H:i:s', time() + $delay)
        ], ['id' => $id]);
    }
    
    public function fail(int $id, string $exception): void
    {
        $job = $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        
        if ($job) {
            // Move to failed jobs table
            $this->db->insert('failed_jobs', [
                'queue' => $job['queue'],
                'payload' => $job['payload'],
                'exception' => $exception,
                'failed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Delete from jobs table
            $this->delete($id);
        }
    }
    
    public function process(array $job): bool
    {
        $payload = json_decode($job['payload'], true);
        $jobClass = $payload['job'];
        $data = $payload['data'];
        
        try {
            if (class_exists($jobClass)) {
                $instance = new $jobClass();
                
                if (method_exists($instance, 'handle')) {
                    $instance->handle($data);
                    return true;
                }
            }
            
            throw new \Exception("Job class {$jobClass} not found or missing handle method");
        } catch (\Exception $e) {
            // Check if should retry
            if ($job['attempts'] < 3) {
                $this->release($job['id'], 60 * pow(2, $job['attempts'])); // Exponential backoff
            } else {
                $this->fail($job['id'], $e->getMessage());
            }
            
            return false;
        }
    }
    
    public function size(string $queue = 'default'): int
    {
        return $this->db->table($this->table)
            ->where('queue', '=', $queue)
            ->where('available_at', '<=', date('Y-m-d H:i:s'))
            ->count();
    }
    
    public function clear(string $queue = 'default'): int
    {
        return $this->db->delete($this->table, ['queue' => $queue]);
    }
}

// Base Job class
abstract class Job
{
    protected int $tries = 3;
    protected int $timeout = 60;
    
    abstract public function handle(array $data): void;
    
    public function failed(\Exception $exception): void
    {
        // Override in child classes for custom failure handling
    }
}