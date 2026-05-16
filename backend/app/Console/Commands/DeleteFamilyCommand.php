<?php

namespace App\Console\Commands;

use App\Core\Services\TenantProvisioningService;
use App\Models\Family;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteFamilyCommand extends Command
{
    protected $signature = 'family:delete {slug : Slug da família a ser removida}';

    protected $description = 'Remove uma família, seu schema tenant e todos os dados relacionados';

    public function __construct(private TenantProvisioningService $provisioner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $family = Family::where('slug', $slug)->first();

        if (! $family) {
            $this->error("Família com slug '{$slug}' não encontrada.");
            return self::FAILURE;
        }

        $this->info("Família: {$family->name} (ID: {$family->id})");
        $this->info("Schema:  " . TenantProvisioningService::schemaName($slug));
        $this->info("Usuários: {$family->members()->count()}");
        $this->newLine();

        if (! $this->confirm('Confirma a exclusão permanente de todos os dados?')) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($family, $slug) {
            $this->provisioner->drop($slug);
            $this->line("Schema dropado.");

            $deleted = $family->members()->delete();
            $this->line("Usuários removidos: {$deleted}");

            $family->delete();
            $this->line("Família removida.");
        });

        $this->newLine();
        $this->info('Concluído.');

        return self::SUCCESS;
    }
}
