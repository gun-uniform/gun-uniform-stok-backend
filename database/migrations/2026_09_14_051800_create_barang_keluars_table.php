<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_keluars', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_transaksi')->unique();
            $table->date('tanggal');

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->cascadeOnDelete();

            // Jumlah yang dikeluarkan.
            // Contoh: 7 PCS
            $table->integer('jumlah');

            // Satuan saat barang keluar.
            // Biasanya mengikuti satuan_keluar dari master barang.
            $table->string('satuan');

            // Jumlah dalam satuan dasar.
            // Karena barang keluar menggunakan satuan dasar,
            // biasanya nilainya sama dengan jumlah.
            // Contoh: 7 PCS = 7 PCS
            $table->integer('jumlah_dasar');

            $table->string('tujuan');
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_keluars');
    }
};
