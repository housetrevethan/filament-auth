<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider_name')->nullable()->after('remember_token');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider_name');
            $table->string('oauth_provider_user_id')->nullable()->after('oauth_provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['oauth_provider_name', 'oauth_provider_id', 'oauth_provider_user_id']);
        });
    }
};
