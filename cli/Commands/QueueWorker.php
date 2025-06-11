<?php

namespace App\Cli\Commands;

use App\Core\Queue;

class QueueWorker extends \Command
{
    private Queue $queue;
    private bool $shouldStop = false;
    
    public function __construct()
    {
        $this->queue = new Queue();
        
        // Handle signals for graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }
    
    public function getName(): string
    {
        return 'queue:work';
    }
    
    public function getDescription(): string
    {
        return 'Process queued jobs';
    }
    
    public function execute(array $args): void
    {
        if ($this->hasOption($args, 'help')) {
            $this->showHelp();
            return;
        }
        
        $queue = $this->getOption($args, 'queue') ?? 'default';
        $sleep = (int) ($this->getOption($args, 'sleep') ?? 3);
        $timeout = (int) ($this->getOption($args, 'timeout') ?? 60);
        $tries = (int) ($this->getOption($args, 'tries') ?? 3);
        
        $this->info("Queue worker started");
        $this->line("Queue: {$queue}");
        $this->line("Sleep: {$sleep}s");
        $this->line("Timeout: {$timeout}s");
        $this->line("Max tries: {$tries}\n");
        
        while (!$this->shouldStop) {
            // Check for signals
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            // Get next job
            $job = $this->queue->pop($queue);
            
            if ($job) {
                $this->processJob($job);
            } else {
                // No jobs, sleep
                sleep($sleep);
            }
        }
        
        $this->info("\nQueue worker stopped gracefully");
    }
    
    private function showHelp(): void
    {
        $this->line("Usage: queue:work [options]");
        $this->line("\nProcess jobs from the queue.");
        $this->line("\nOptions:");
        $this->line("  --queue=NAME     The queue to process (default: default)");
        $this->line("  --sleep=SECONDS  Seconds to sleep when no jobs (default: 3)");
        $this->line("  --timeout=SECONDS  Timeout for each job (default: 60)");
        $this->line("  --tries=NUMBER   Number of attempts (default: 3)");
    }
    
    private function processJob(array $job): void
    {
        $payload = json_decode($job['payload'], true);
        
        $this->line("Processing job: {$payload['job']} (ID: {$job['id']})");
        
        $startTime = microtime(true);
        
        try {
            $success = $this->queue->process($job);
            
            if ($success) {
                $this->queue->delete($job['id']);
                $duration = round(microtime(true) - $startTime, 2);
                $this->info("Job completed in {$duration}s");
            }
        } catch (\Exception $e) {
            $this->error("Job failed: " . $e->getMessage());
        }
    }
    
    public function handleSignal(int $signal): void
    {
        $this->shouldStop = true;
        $this->warning("\nReceived signal {$signal}, shutting down...");
    }
}