<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $family = Family::create(['name' => 'Test Family', 'slug' => 'test-family']);
        $this->user = User::factory()->create([
            'family_id' => $family->id,
            'email'     => 'user@test.com',
            'password'  => bcrypt('password123'),
        ]);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 200)
                 ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@test.com',
            'password' => 'senha_errada',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'naoexiste@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_without_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
    }

    public function test_logout_invalidates_token(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        $this->withToken($token)
             ->postJson('/api/v1/auth/logout')
             ->assertStatus(200);

        // Token revogado — próxima request deve retornar 401
        $this->withToken($token)
             ->postJson('/api/v1/auth/logout')
             ->assertStatus(401);
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')
             ->assertStatus(401);
    }
}
