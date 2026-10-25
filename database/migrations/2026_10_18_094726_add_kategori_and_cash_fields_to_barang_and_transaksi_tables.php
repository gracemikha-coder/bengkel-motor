<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('barang') && ! Schema::hasColumn('barang', 'kategori')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->string('kategori', 50)->default('Lainnya')->after('nama');
            });

            DB::table('barang')->update([
                'kategori' => DB::raw("CASE
                    WHEN LOWER(nama) LIKE '%oli%' OR LOWER(nama) LIKE '%minyak%' OR LOWER(nama) LIKE '%coolant%' THEN 'Oli'
                    WHEN LOWER(nama) LIKE '%busi%' OR LOWER(nama) LIKE '%spark plug%' THEN 'Busi'
                    WHEN LOWER(nama) LIKE '%ban%' OR LOWER(nama) LIKE '%tire%' OR LOWER(nama) LIKE '%tyre%' THEN 'Ban'
                    WHEN LOWER(nama) LIKE '%aki%' OR LOWER(nama) LIKE '%accu%' OR LOWER(nama) LIKE '%battery%' THEN 'Aki'
                    WHEN LOWER(nama) LIKE '%filter%' THEN 'Filter'
                    ELSE 'Lainnya'
                END"),
            ]);
        }

        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                if (! Schema::hasColumn('transaksi', 'uang_dibayar')) {
                    $table->unsignedInteger('uang_dibayar')->default(0)->after('diskon_persen');
                }

                if (! Schema::hasColumn('transaksi', 'kembalian')) {
                    $table->unsignedInteger('kembalian')->default(0)->after('uang_dibayar');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $columns = array_filter(['uang_dibayar', 'kembalian'], fn ($column) => Schema::hasColumn('transaksi', $column));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('barang') && Schema::hasColumn('barang', 'kategori')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->dropColumn('kategori');
            });
        }
    }
};
