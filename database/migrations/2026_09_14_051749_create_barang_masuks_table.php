<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_masuks', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_transaksi')->unique();
            $table->date('tanggal');

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->cascadeOnDelete();

            // Jumlah yang benar-benar diinput user.
            // Contoh: 5 Pack atau 50 PCS
            $table->integer('jumlah');

            // Satuan saat transaksi masuk.
            // Contoh: Pack, Dus, Box, PCS
            $table->string('satuan');

            // Jumlah dalam satuan dasar.
            // Contoh:
            // 5 Pack x 25 = 125 PCS
            // 50 PCS = 50 PCS
            $table->integer('jumlah_dasar');

            $table->string('supplier')->nullable();
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_masuks');
    }
};
