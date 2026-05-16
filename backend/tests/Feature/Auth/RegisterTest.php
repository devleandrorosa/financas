<?php

namespace Tests\Feature\Auth;

use App\Core\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'name'                  => 'Leandro Rosa',
        'email'                 => 'leandro@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'family_name'           => 'Família Rosa',
    ];

    public function test_register_returns_201_with_token_and_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->valid);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 201)
                 ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);
    }

    public function test_register_creates_user_in_database(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid);

        $this->assertDatabaseHas('users', ['email' => 'leandro@test.com']);
    }

    public function test_register_creates_family_in_database(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid);

        $this->assertDatabaseHas('families', ['name' => 'Família Rosa']);
    }

    public function test_register_provisions_tenant_schema(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid);

        $family = DB::table('families')->where('name', 'Família Rosa')->first();
        $schema = TenantProvisioningService::schemaName($family->slug);

        $exists = DB::selectOne(
            "SELECT 1 FROM information_schema.schemata WHERE schema_name = ?",
            [$schema]
        );

        $this->assertNotNull($exists, "Schema {$schema} não foi criado.");
    }

    public function test_register_seeds_default_categories(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid);

        $family = DB::table('families')->where('name', 'Família Rosa')->first();
        $schema = TenantProvisioningService::schemaName($family->slug);

        $count = DB::table("{$schema}.categories")->count();
        $this->assertGreaterThan(0, $count, 'Categorias padrão não foram criadas.');
    }

    public function test_register_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        $this->postJson('/api/v1/auth/register', $this->valid);

        $response = $this->postJson('/api/v1/auth/register', array_merge($this->valid, [
            'family_name' => 'Outra Família',
        ]));

        $response->assertStatus(422)
                 ->assertJsonPath('errors.email.0', fn($v) => str_contains($v, 'already') || str_contains($v, 'tomado') || str_contains($v, 'utilizado'));
    }

    public function test_register_fails_when_passwords_do_not_match(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->valid, [
            'password_confirmation' => 'outra_senha',
        ]));

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['password']]);
    }
}
