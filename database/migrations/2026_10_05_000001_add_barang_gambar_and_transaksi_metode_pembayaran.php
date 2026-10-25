<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barang') && ! Schema::hasColumn('barang', 'gambar')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->string('gambar')->nullable();
            });
        }

        if (Schema::hasTable('transaksi') && ! Schema::hasColumn('transaksi', 'metode_pembayaran')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('metode_pembayaran', ['tunai', 'qris'])->default('tunai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi') && Schema::hasColumn('transaksi', 'metode_pembayaran')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('metode_pembayaran');
            });
        }

        if (Schema::hasTable('barang') && Schema::hasColumn('barang', 'gambar')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->dropColumn('gambar');
            });
        }
    }
};
