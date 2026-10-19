<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();

            $table->string('kode_barang')->unique();
            $table->string('nama_barang');
            $table->string('detail_ukuran')->nullable();

            // Satuan yang digunakan saat barang masuk
            $table->string('satuan_masuk');

            // Isi dalam satu satuan masuk.
            // Contoh:
            // Pack Topi = 25 PCS
            // Pack Kaos Kaki = 12 PCS
            // Untuk barang yang masuk langsung dalam PCS, boleh NULL.
            $table->integer('isi_per_satuan')->nullable();

            // Satuan dasar/keluar barang
            // Biasanya PCS.
            $table->string('satuan_keluar')->default('PCS');

            // Batas minimum stok
            $table->integer('stok_minimum')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
