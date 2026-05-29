<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action to the audit logs.
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?array $metadata = null,
        ?int $actorId = null
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'correlation_id' => Context::get('correlation_id'),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
