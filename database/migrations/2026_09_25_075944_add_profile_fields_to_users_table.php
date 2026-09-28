<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('role', ['admin', 'operator', 'viewer'])
                  ->default('viewer')->after('phone');
            $table->enum('status', ['pending', 'approved', 'rejected', 'nonaktif'])
                  ->default('pending')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'phone', 'role', 'status']);
        });
    }
};
