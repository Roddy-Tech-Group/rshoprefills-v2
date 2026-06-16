<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Update the public support phone + WhatsApp number to the current line. These
 * keys already exist (seeded in 2026_05_30_181000_seed_comprehensive_site_settings)
 * and stay admin-editable on the System Settings page; this resets the stored
 * value so every environment shows the new number after deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::put('contact.phone_primary', '+1 (940) 238-6229', 'contact', 'Primary phone number (E.164 format). Used for the contact page + emails.');
        SiteSetting::put('contact.whatsapp_number', '19402386229', 'contact', 'WhatsApp number without +. Powers wa.me links on the contact + support widgets.');
    }

    public function down(): void
    {
        // No-op: we don't restore the previous number.
    }
};
