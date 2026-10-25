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
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
        });

        $legacyCategories = Schema::hasTable('barang') && Schema::hasColumn('barang', 'kategori')
            ? DB::table('barang')->whereNotNull('kategori')->distinct()->pluck('kategori')
            : collect();

        foreach ($legacyCategories as $legacyCategory) {
            $categoryName = trim((string) $legacyCategory) ?: 'Lainnya';
            DB::table('kategori')->insertOrIgnore(['nama' => $categoryName]);
        }

        DB::table('kategori')->insertOrIgnore(['nama' => 'Lainnya']);

        if (Schema::hasTable('barang') && ! Schema::hasColumn('barang', 'kategori_id')) {
            Schema::table('barang', function (Blueprint $table) {
                $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            });

            foreach ($legacyCategories as $legacyCategory) {
                $categoryName = trim((string) $legacyCategory) ?: 'Lainnya';
                $categoryId = DB::table('kategori')->where('nama', $categoryName)->value('id');

                DB::table('barang')->where('kategori', $legacyCategory)->update(['kategori_id' => $categoryId]);
            }

            $otherCategoryId = DB::table('kategori')->where('nama', 'Lainnya')->value('id');
            DB::table('barang')->whereNull('kategori_id')->update(['kategori_id' => $otherCategoryId]);

            if (Schema::hasColumn('barang', 'kategori')) {
                Schema::table('barang', function (Blueprint $table) {
                    $table->dropColumn('kategori');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('barang') && Schema::hasColumn('barang', 'kategori_id')) {
            if (! Schema::hasColumn('barang', 'kategori')) {
                Schema::table('barang', function (Blueprint $table) {
                    $table->string('kategori', 50)->nullable();
                });
            }

            foreach (DB::table('kategori')->get(['id', 'nama']) as $category) {
                DB::table('barang')->where('kategori_id', $category->id)->update(['kategori' => $category->nama]);
            }

            Schema::table('barang', function (Blueprint $table) {
                $table->dropForeign(['kategori_id']);
                $table->dropColumn('kategori_id');
            });
        }

        Schema::dropIfExists('kategori');
    }
};
