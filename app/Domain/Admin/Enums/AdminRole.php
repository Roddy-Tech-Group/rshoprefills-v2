<?php

namespace App\Domain\Admin\Enums;

/**
 * Admin role definitions.
 *
 * Stored as string values in the database for readability and
 * future extensibility. New roles can be added without schema
 * changes — just add a new case and reference it in policies.
 */
enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Moderator = 'moderator';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Moderator => 'Moderator',
        };
    }

    /**
     * Whether this role has full unrestricted access.
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }
}
