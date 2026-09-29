<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('usaha', function (Blueprint $table) {
        $table->dropColumn('alamat');
    });

    Schema::table('rumah_tangga', function (Blueprint $table) {
        $table->dropColumn('alamat');
    });
}

public function down(): void
{
    Schema::table('usaha', function (Blueprint $table) {
        $table->text('alamat')->nullable();
    });

    Schema::table('rumah_tangga', function (Blueprint $table) {
        $table->text('alamat')->nullable();
    });
}
};
