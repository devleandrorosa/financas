<?php

namespace Tests;

use App\Core\Services\TenantProvisioningService;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Base para testes de rotas tenant (auth:sanctum + tenant middleware).
 *
 * O PostgreSQL suporta DDL transacional, então o schema criado em setUp()
 * é revertido automaticamente pelo RefreshDatabase junto com os dados.
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Family $family;
    protected string $token;
    protected string $testSlug = 'test-family';

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::create([
            'name' => 'Test Family',
            'slug' => $this->testSlug,
        ]);

        $this->user = User::factory()->create([
            'family_id' => $this->family->id,
        ]);

        app(TenantProvisioningService::class)->provision($this->testSlug);

        $schema = TenantProvisioningService::schemaName($this->testSlug);
        DB::statement("SET search_path = \"{$schema}\", public");

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    /** Cria um registro na tabela tenant atual. */
    protected function tenantInsert(string $table, array $data): int
    {
        return DB::table($table)->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
