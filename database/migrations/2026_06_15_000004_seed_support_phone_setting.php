<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Global support phone number shown on the contact page (Call / iMessage /
 * WhatsApp). Admin-editable on the System Settings page (group "contact"); the
 * blade derives the dialable and WhatsApp forms from this display value.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! SiteSetting::query()->where('key', 'contact.support_phone')->exists()) {
            SiteSetting::put(
                'contact.support_phone',
                '+237 676 700 173',
                'contact',
                'Global support phone (Call / iMessage / WhatsApp), shown on the contact page.',
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('key', 'contact.support_phone')->delete();
    }
};
