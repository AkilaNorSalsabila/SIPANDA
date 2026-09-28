<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->enum('jenis_data', ['batas_sls', 'titik_lokasi']);
            $table->string('nama_file');

            $table->unsignedInteger('total_data')->default(0);
            $table->unsignedInteger('data_valid')->default(0);
            $table->unsignedInteger('data_error')->default(0);

            $table->enum('status', ['processing', 'success', 'failed'])->default('processing');

            // Detail baris yang error, disimpan sebagai JSON:
            // [{ "baris": 23, "pesan": "ID SLS tidak ditemukan" }, ...]
            $table->json('error_log')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
