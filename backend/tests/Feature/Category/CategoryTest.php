<?php

namespace Tests\Feature\Category;

use Tests\TenantTestCase;

class CategoryTest extends TenantTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────────

    private function createCategory(array $override = []): int
    {
        return $this->tenantInsert('categories', array_merge([
            'name'      => 'Alimentação',
            'type'      => 'expense',
            'parent_id' => null,
            'color'     => '#FF5733',
        ], $override));
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function test_list_categories_returns_200(): void
    {
        $this->createCategory();

        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/categories');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_list_returns_seeded_categories(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/categories');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_flat_list_returns_all_categories(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->getJson('/api/v1/categories/flat');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_create_category_returns_201(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/categories', [
                             'name' => 'Transporte',
                             'type' => 'expense',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Transporte')
                 ->assertJsonPath('data.type', 'expense');
    }

    public function test_create_category_fails_without_name(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/categories', ['type' => 'expense']);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_create_child_category(): void
    {
        $parentId = $this->createCategory(['name' => 'Moradia']);

        $response = $this->withHeaders($this->authHeaders())
                         ->postJson('/api/v1/categories', [
                             'name'      => 'Aluguel',
                             'type'      => 'expense',
                             'parent_id' => $parentId,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.parent_id', $parentId);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function test_update_category_returns_200(): void
    {
        $id = $this->createCategory(['name' => 'Antigo']);

        $response = $this->withHeaders($this->authHeaders())
                         ->putJson("/api/v1/categories/{$id}", [
                             'name' => 'Novo Nome',
                             'type' => 'expense',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Novo Nome');
    }

    public function test_update_nonexistent_category_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->putJson('/api/v1/categories/99999', [
                             'name' => 'X',
                             'type' => 'expense',
                         ]);

        $response->assertStatus(404);
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function test_delete_category_returns_200(): void
    {
        $id = $this->createCategory();

        $response = $this->withHeaders($this->authHeaders())
                         ->deleteJson("/api/v1/categories/{$id}");

        $response->assertStatus(200);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    }
}
