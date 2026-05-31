<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add downloadable attachment fields to blog_posts. Same shape as the
     * press_articles attachment columns — uploaded file lands in
     * public/assets/blog/<filename>, path stored in `attachment_path`,
     * download button labelled with `attachment_label`.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('image');
            $table->string('attachment_label', 80)->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_label']);
        });
    }
};
