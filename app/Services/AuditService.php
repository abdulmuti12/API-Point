<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        string $action,
        ?string $userId = null,
        ?string $userType = null,
        ?string $userName = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
        ?Request $request = null
    ): void {
        try {
            $audit = new AuditLog();
            $audit->user_id = $userId;
            $audit->user_type = $userType;
            $audit->user_name = $userName;
            $audit->action = $action;
            $audit->entity_type = $entityType;
            $audit->entity_id = $entityId;
            $audit->description = $description;
            $audit->ip_address = $request?->ip();
            $audit->user_agent = $request?->userAgent();
            $audit->created_at = now();
            $audit->save();
        } catch (\Throwable $e) {
            \Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
