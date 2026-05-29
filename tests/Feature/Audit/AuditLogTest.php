<?php

namespace Tests\Feature\Audit;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Wallet\Events\TransactionPinCreated;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_service_writes_an_audit_entry(): void
    {
        $user = User::factory()->create();

        app(AuditLogService::class)->log('test_action', $user, null, null, ['key' => 'value'], $user->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'actor_id' => $user->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    public function test_a_transaction_pin_created_event_is_audited(): void
    {
        $user = User::factory()->create();

        event(new TransactionPinCreated($user));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_pin_created',
            'actor_id' => $user->id,
        ]);
    }

    public function test_a_successful_login_is_audited(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_login',
            'actor_id' => $user->id,
        ]);
    }
}
