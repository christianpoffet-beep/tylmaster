<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('music_submissions', function (Blueprint $table) {
            // Contact details
            $table->string('first_name')->nullable()->after('artist_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('street')->nullable()->after('phone');
            $table->string('zip')->nullable()->after('street');
            $table->string('city')->nullable()->after('zip');
            $table->string('country')->nullable()->after('city');
            $table->string('iban')->nullable()->after('country');
            $table->string('account_holder')->nullable()->after('iban');
            $table->string('bank_name')->nullable()->after('account_holder');

            // Release details
            $table->string('project_name')->nullable()->after('genre');
            $table->string('subgenre')->nullable()->after('project_name');
            $table->string('explicit')->nullable()->after('subgenre');
            $table->date('release_date')->nullable()->after('explicit');
            $table->string('upc')->nullable()->after('release_date');
            $table->string('year_composition')->nullable()->after('upc');
            $table->string('year_recording')->nullable()->after('year_composition');
            $table->text('other_credits')->nullable()->after('year_recording');

            // Media & Bio
            $table->string('cover_image_path')->nullable()->after('file_path');
            $table->text('bio_short')->nullable()->after('cover_image_path');
            $table->text('bio_long')->nullable()->after('bio_short');
            $table->string('website')->nullable()->after('bio_long');
            $table->string('spotify_link')->nullable()->after('website');
            $table->string('instagram')->nullable()->after('spotify_link');
            $table->string('social_other')->nullable()->after('instagram');

            // Contract
            $table->string('contract_excluded_countries')->nullable()->after('social_other');
            $table->date('contract_end_date')->nullable()->after('contract_excluded_countries');
            $table->string('contract_advance_interest')->nullable()->after('contract_end_date');
            $table->string('digital_signature')->nullable()->after('contract_advance_interest');
            $table->date('contract_sign_date')->nullable()->after('digital_signature');

            // Songs data (JSON array of song objects)
            $table->json('songs_data')->nullable()->after('contract_sign_date');

            // Promo photos (JSON array of file paths)
            $table->json('promo_photos')->nullable()->after('songs_data');

            // Payment
            $table->decimal('calculated_price', 10, 2)->nullable()->after('promo_photos');
            $table->integer('song_count')->nullable()->after('calculated_price');
            $table->string('payment_status')->default('pending')->after('song_count');

            // Access code from submission site
            $table->string('access_code')->nullable()->after('payment_status');

            // Links to created records
            $table->foreignId('release_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->after('release_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('music_submissions', function (Blueprint $table) {
            $table->dropForeign(['release_id']);
            $table->dropForeign(['contract_id']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'street', 'zip', 'city', 'country',
                'iban', 'account_holder', 'bank_name',
                'project_name', 'subgenre', 'explicit', 'release_date', 'upc',
                'year_composition', 'year_recording', 'other_credits',
                'cover_image_path', 'bio_short', 'bio_long', 'website', 'spotify_link',
                'instagram', 'social_other',
                'contract_excluded_countries', 'contract_end_date', 'contract_advance_interest',
                'digital_signature', 'contract_sign_date',
                'songs_data', 'promo_photos', 'calculated_price', 'song_count',
                'payment_status', 'access_code', 'release_id', 'contract_id',
            ]);
        });
    }
};
