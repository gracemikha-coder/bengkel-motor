<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BarangControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->integer('harga_beli');
            $table->integer('harga_jual');
            $table->integer('stok')->default(0);
            $table->integer('batas_menipis')->default(5);
            $table->string('gambar')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('barang_masuk', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('barang_id')->constrained('barang');
            $table->integer('jumlah');
            $table->integer('harga_beli');
            $table->string('toko')->nullable();
        });

        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->nullable()->constrained('barang');
        });

        Schema::create('log_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang');
        });

        Schema::create('jasa', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->integer('tarif');
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->integer('total')->default(0);
        });
    }

    public function test_store_rolls_back_barang_when_initial_stock_insert_fails(): void
    {
        DB::statement("CREATE TRIGGER reject_barang_masuk BEFORE INSERT ON barang_masuk BEGIN SELECT RAISE(ABORT, 'stock entry rejected'); END");

        $this->actingAs(User::factory()->create())
            ->from(route('katalog'))
            ->post(route('barang.store'), $this->validBarangData(['stok_awal' => 2]))
            ->assertRedirect(route('katalog'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('stock entry rejected', session('error'));
        $this->assertDatabaseMissing('barang', ['nama' => 'Busi Test']);
        $this->assertDatabaseCount('barang_masuk', 0);
    }

    public function test_store_saves_barang_and_initial_stock_entry(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('barang.store'), $this->validBarangData(['stok_awal' => 2]))
            ->assertSessionHas('success', 'Barang berhasil ditambah.');

        $this->assertDatabaseHas('barang', ['nama' => 'Busi Test']);
        $this->assertDatabaseHas('barang_masuk', [
            'jumlah' => 2,
            'harga_beli' => 10000,
            'toko' => 'Stok awal',
        ]);
    }

    public function test_destroy_deactivates_barang_without_removing_transaction_history(): void
    {
        $barangId = $this->createBarang();
        DB::table('detail_transaksi')->insert(['barang_id' => $barangId]);

        $this->actingAs(User::factory()->create())
            ->from(route('katalog'))
            ->delete(route('barang.destroy', $barangId))
            ->assertRedirect(route('katalog'))
            ->assertSessionHas('success', 'Barang berhasil dinonaktifkan.');

        $this->assertDatabaseHas('barang', ['id' => $barangId, 'is_active' => false]);
        $this->assertDatabaseHas('detail_transaksi', ['barang_id' => $barangId]);
    }

    public function test_catalog_only_displays_active_barang(): void
    {
        $this->createBarang('Barang Aktif', true, 2);
        $this->createBarang('Barang Nonaktif', false, 2);

        $this->actingAs(User::factory()->create())
            ->get(route('katalog'))
            ->assertOk()
            ->assertSee('Barang Aktif')
            ->assertDontSee('Barang Nonaktif');
    }

    public function test_cashier_only_displays_active_barang(): void
    {
        $this->createBarang('Barang Aktif POS', true, 2);
        $this->createBarang('Barang Nonaktif POS', false, 2);

        $this->actingAs(User::factory()->create())
            ->get(route('kasir'))
            ->assertOk()
            ->assertSee('Barang Aktif POS')
            ->assertDontSee('Barang Nonaktif POS');
    }

    private function validBarangData(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Busi Test',
            'kategori_id' => DB::table('kategori')->value('id'),
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'batas_menipis' => 5,
            'stok_awal' => 0,
        ], $overrides);
    }

    private function createBarang(string $name = 'Barang Terkait', bool $isActive = true, int $stock = 0): int
    {
        return (int) DB::table('barang')->insertGetId([
            'nama' => $name,
            'kategori_id' => DB::table('kategori')->value('id'),
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => $stock,
            'batas_menipis' => 5,
            'is_active' => $isActive,
        ]);
    }
}
