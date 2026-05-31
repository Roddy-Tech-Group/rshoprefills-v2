<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add downloadable attachment fields to press_articles. When the editor
     * uploads a press kit / PDF / zip / etc. through the admin form, the
     * file lives at public/assets/press/<filename>, the relative path is
     * stored in `attachment_path`, and the public press post page renders
     * a download button labelled with `attachment_label`.
     */
    public function up(): void
    {
        Schema::table('press_articles', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('image');
            $table->string('attachment_label', 80)->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('press_articles', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_label']);
        });
    }
};
