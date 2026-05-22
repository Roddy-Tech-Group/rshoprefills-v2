<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private string $serviceName;
    private int $maxFailures;
    private int $decayMinutes;

    public function __construct(string $serviceName, int $maxFailures = 10, int $decayMinutes = 5)
    {
        $this->serviceName = $serviceName;
        $this->maxFailures = $maxFailures;
        $this->decayMinutes = $decayMinutes;
    }

    private function getCacheKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    public function recordFailure(): void
    {
        $key = $this->getCacheKey();
        
        $failures = Cache::tags(['circuit_breaker'])->get($key, 0);
        $failures++;
        
        Cache::tags(['circuit_breaker'])->put($key, $failures, now()->addMinutes($this->decayMinutes));
    }

    public function recordSuccess(): void
    {
        Cache::tags(['circuit_breaker'])->forget($this->getCacheKey());
    }

    public function isOpen(): bool
    {
        $failures = Cache::tags(['circuit_breaker'])->get($this->getCacheKey(), 0);
        return $failures >= $this->maxFailures;
    }
}
