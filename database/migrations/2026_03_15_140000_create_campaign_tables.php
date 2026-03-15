<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kampagnenvorlagen (Templates)
        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('email'); // email, letter (future)
            $table->string('language', 5)->default('de');
            $table->string('subject')->nullable(); // E-Mail Betreff
            $table->longText('body')->nullable(); // HTML-Inhalt
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Kampagnen
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('email'); // email, letter (future)
            $table->string('status')->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->string('language', 5)->default('de');
            $table->foreignId('campaign_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('address_circle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('campaign_templates');
    }
};
