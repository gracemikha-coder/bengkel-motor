<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_category(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('kategori.store'), ['nama' => 'Kampas Rem']);

        $response->assertRedirect(route('katalog'))
            ->assertSessionHas('success', 'Kategori berhasil ditambahkan.');
        $this->assertDatabaseHas('kategori', ['nama' => 'Kampas Rem']);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $user = User::factory()->create();
        Kategori::create(['nama' => 'Kampas Rem']);

        $this->actingAs($user)
            ->from(route('katalog'))
            ->post(route('kategori.store'), ['nama' => 'Kampas Rem'])
            ->assertSessionHasErrors('nama')
            ->assertRedirect(route('katalog'));
    }

    public function test_guest_cannot_create_a_category(): void
    {
        $this->post(route('kategori.store'), ['nama' => 'Kampas Rem'])
            ->assertRedirect(route('login'));
    }
}
