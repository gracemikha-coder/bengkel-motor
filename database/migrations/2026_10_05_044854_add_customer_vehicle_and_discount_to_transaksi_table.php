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
        if (! Schema::hasTable('transaksi')) {
            return;
        }

        Schema::table('transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi', 'pelanggan')) {
                $table->string('pelanggan', 120)->nullable();
            }
            if (! Schema::hasColumn('transaksi', 'nomor_plat')) {
                $table->string('nomor_plat', 20)->nullable();
            }
            if (! Schema::hasColumn('transaksi', 'diskon_persen')) {
                $table->decimal('diskon_persen', 5, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $columns = collect(['pelanggan', 'nomor_plat', 'diskon_persen'])
                    ->filter(fn ($column) => Schema::hasColumn('transaksi', $column))
                    ->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
