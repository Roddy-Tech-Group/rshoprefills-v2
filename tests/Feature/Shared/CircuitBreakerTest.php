<?php

namespace Tests\Feature\Shared;

use App\Domain\Shared\Services\CircuitBreaker;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    public function test_it_starts_closed(): void
    {
        $breaker = new CircuitBreaker('svc-start', maxFailures: 3);

        $this->assertFalse($breaker->isOpen());
    }

    public function test_it_opens_only_after_reaching_max_failures(): void
    {
        $breaker = new CircuitBreaker('svc-open', maxFailures: 3);

        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertFalse($breaker->isOpen());

        $breaker->recordFailure();
        $this->assertTrue($breaker->isOpen());
    }

    public function test_a_success_resets_the_failure_count(): void
    {
        $breaker = new CircuitBreaker('svc-reset', maxFailures: 2);

        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertTrue($breaker->isOpen());

        $breaker->recordSuccess();
        $this->assertFalse($breaker->isOpen());
    }

    public function test_breakers_are_isolated_by_service_name(): void
    {
        $a = new CircuitBreaker('svc-a', maxFailures: 1);
        $b = new CircuitBreaker('svc-b', maxFailures: 1);

        $a->recordFailure();

        $this->assertTrue($a->isOpen());
        $this->assertFalse($b->isOpen());
    }
}
