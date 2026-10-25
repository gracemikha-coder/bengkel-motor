<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->integer('total')->default(0);
        });

        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_id');
            $table->unsignedBigInteger('barang_id')->nullable();
            $table->unsignedBigInteger('jasa_id')->nullable();
            $table->integer('jumlah');
            $table->integer('harga_jual');
            $table->integer('harga_beli')->default(0);
        });
    }

    public function test_authenticated_user_can_download_daily_sales_totals_as_csv(): void
    {
        $this->freezeTime();
        $date = now()->toDateString();
        $firstTransactionId = DB::table('transaksi')->insertGetId([
            'tanggal' => $date.' 09:00:00',
            'total' => 40000,
        ]);
        $secondTransactionId = DB::table('transaksi')->insertGetId([
            'tanggal' => $date.' 10:00:00',
            'total' => 5000,
        ]);

        DB::table('detail_transaksi')->insert([
            ['transaksi_id' => $firstTransactionId, 'barang_id' => 1, 'jasa_id' => null, 'jumlah' => 2, 'harga_jual' => 15000, 'harga_beli' => 10000],
            ['transaksi_id' => $firstTransactionId, 'barang_id' => null, 'jasa_id' => 1, 'jumlah' => 1, 'harga_jual' => 10000, 'harga_beli' => 0],
            ['transaksi_id' => $secondTransactionId, 'barang_id' => 2, 'jasa_id' => null, 'jumlah' => 1, 'harga_jual' => 5000, 'harga_beli' => 3000],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('laporan.export'));

        $response->assertDownload('laporan-weekly-'.$date.'.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertStreamedContent("\xEF\xBB\xBFTanggal;\"Jumlah Transaksi\";\"Pendapatan Barang (Rp)\";\"Pendapatan Jasa (Rp)\";\"Uang Masuk (Rp)\";\"Modal (Rp)\";\"Untung Bersih (Rp)\"\n{$date};2;35000;10000;45000;23000;22000\n");
    }

    public function test_monthly_export_combines_transactions_from_the_same_month(): void
    {
        $this->freezeTime();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $firstTransactionId = DB::table('transaksi')->insertGetId([
            'tanggal' => $yesterday.' 09:00:00',
            'total' => 20000,
        ]);
        $secondTransactionId = DB::table('transaksi')->insertGetId([
            'tanggal' => $today.' 10:00:00',
            'total' => 15000,
        ]);

        DB::table('detail_transaksi')->insert([
            ['transaksi_id' => $firstTransactionId, 'barang_id' => 1, 'jasa_id' => null, 'jumlah' => 2, 'harga_jual' => 10000, 'harga_beli' => 5000],
            ['transaksi_id' => $secondTransactionId, 'barang_id' => null, 'jasa_id' => 1, 'jumlah' => 1, 'harga_jual' => 15000, 'harga_beli' => 0],
        ]);

        $month = now()->format('Y-m');
        $response = $this->actingAs(User::factory()->create())
            ->get(route('laporan.export', ['mode' => 'monthly']));

        $response->assertDownload('laporan-monthly-'.$today.'.csv')
            ->assertStreamedContent("\xEF\xBB\xBFTanggal;\"Jumlah Transaksi\";\"Pendapatan Barang (Rp)\";\"Pendapatan Jasa (Rp)\";\"Uang Masuk (Rp)\";\"Modal (Rp)\";\"Untung Bersih (Rp)\"\n{$month};2;20000;15000;35000;10000;25000\n");
    }

    public function test_sql_backup_and_restore_endpoints_are_removed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/backup')->assertNotFound();
        $this->post('/pulihkan')->assertNotFound();
    }

    public function test_guest_is_redirected_from_the_csv_download_route(): void
    {
        $this->get(route('laporan.export'))
            ->assertRedirect(route('login'));
    }
}
