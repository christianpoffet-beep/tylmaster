<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which party got its statement by e-mail, when and where - or why not (also test mails). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statement_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('status', 10); // sent, failed, test
            $table->string('message_id')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_statement_mails');
    }
};
