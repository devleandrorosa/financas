<?php

namespace Tests\Unit\Services;

use App\Core\Services\TenantProvisioningService;
use Tests\TestCase;

class TenantProvisioningServiceTest extends TestCase
{
    public function test_schema_name_converts_hyphens_to_underscores(): void
    {
        $this->assertSame('family_minha_familia', TenantProvisioningService::schemaName('minha-familia'));
    }

    public function test_schema_name_keeps_underscores(): void
    {
        $this->assertSame('family_minha_familia', TenantProvisioningService::schemaName('minha_familia'));
    }

    public function test_schema_name_handles_multiple_hyphens(): void
    {
        $this->assertSame('family_rosa_e_silva', TenantProvisioningService::schemaName('rosa-e-silva'));
    }

    public function test_schema_name_prefixes_with_family(): void
    {
        $this->assertSame('family_silva', TenantProvisioningService::schemaName('silva'));
    }

    public function test_schema_name_handles_single_word(): void
    {
        $schema = TenantProvisioningService::schemaName('familia');
        $this->assertStringStartsWith('family_', $schema);
    }
}
