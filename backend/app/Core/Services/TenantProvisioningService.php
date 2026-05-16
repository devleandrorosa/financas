<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public static function schemaName(string $slug): string
    {
        return 'family_' . str_replace('-', '_', $slug);
    }

    public function provision(string $slug): void
    {
        $schema = self::schemaName($slug);

        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
        DB::statement("SET search_path = \"{$schema}\", public");

        $this->createTables($schema);

        DB::statement('SET search_path = public');
    }

    public function drop(string $slug): void
    {
        $schema = self::schemaName($slug);
        DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
    }

    private function createTables(string $schema): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".categories (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            parent_id BIGINT REFERENCES \"{$schema}\".categories(id) ON DELETE SET NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'expense',
            color VARCHAR(7) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".bank_accounts (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            bank VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'checking',
            balance BIGINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".credit_cards (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            bank VARCHAR(255) NOT NULL,
            limit_amount BIGINT NOT NULL DEFAULT 0,
            closing_day SMALLINT NOT NULL,
            due_day SMALLINT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".credit_card_statements (
            id BIGSERIAL PRIMARY KEY,
            credit_card_id BIGINT NOT NULL REFERENCES \"{$schema}\".credit_cards(id) ON DELETE CASCADE,
            year SMALLINT NOT NULL,
            month SMALLINT NOT NULL,
            total_amount BIGINT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            UNIQUE(credit_card_id, year, month)
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".transactions (
            id BIGSERIAL PRIMARY KEY,
            description VARCHAR(255) NOT NULL,
            amount BIGINT NOT NULL,
            type VARCHAR(20) NOT NULL,
            date DATE NOT NULL,
            category_id BIGINT REFERENCES \"{$schema}\".categories(id) ON DELETE SET NULL,
            bank_account_id BIGINT REFERENCES \"{$schema}\".bank_accounts(id) ON DELETE SET NULL,
            credit_card_id BIGINT REFERENCES \"{$schema}\".credit_cards(id) ON DELETE SET NULL,
            credit_card_statement_id BIGINT REFERENCES \"{$schema}\".credit_card_statements(id) ON DELETE SET NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
            notes TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".installments (
            id BIGSERIAL PRIMARY KEY,
            transaction_id BIGINT NOT NULL REFERENCES \"{$schema}\".transactions(id) ON DELETE CASCADE,
            credit_card_statement_id BIGINT NOT NULL REFERENCES \"{$schema}\".credit_card_statements(id) ON DELETE CASCADE,
            number SMALLINT NOT NULL,
            total SMALLINT NOT NULL,
            amount BIGINT NOT NULL,
            date DATE NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".recurring_rules (
            id BIGSERIAL PRIMARY KEY,
            description VARCHAR(255) NOT NULL,
            amount BIGINT NOT NULL,
            type VARCHAR(20) NOT NULL,
            frequency VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            category_id BIGINT REFERENCES \"{$schema}\".categories(id) ON DELETE SET NULL,
            bank_account_id BIGINT REFERENCES \"{$schema}\".bank_accounts(id) ON DELETE SET NULL,
            active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".investments (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            institution VARCHAR(255) NOT NULL,
            amount BIGINT NOT NULL DEFAULT 0,
            purchased_at DATE NOT NULL,
            maturity_at DATE NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".budgets (
            id BIGSERIAL PRIMARY KEY,
            category_id BIGINT NOT NULL REFERENCES \"{$schema}\".categories(id) ON DELETE CASCADE,
            year SMALLINT NOT NULL,
            month SMALLINT NOT NULL,
            amount BIGINT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            UNIQUE(category_id, year, month)
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".goals (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'savings',
            target_amount BIGINT NOT NULL,
            current_amount BIGINT NOT NULL DEFAULT 0,
            deadline DATE NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".ai_import_sessions (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'processing',
            error_message TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$schema}\".ai_import_items (
            id BIGSERIAL PRIMARY KEY,
            session_id BIGINT NOT NULL REFERENCES \"{$schema}\".ai_import_sessions(id) ON DELETE CASCADE,
            description VARCHAR(255) NOT NULL,
            amount BIGINT NOT NULL,
            type VARCHAR(20) NOT NULL,
            date DATE NOT NULL,
            category_id BIGINT REFERENCES \"{$schema}\".categories(id) ON DELETE SET NULL,
            bank_account_id BIGINT REFERENCES \"{$schema}\".bank_accounts(id) ON DELETE SET NULL,
            credit_card_id BIGINT REFERENCES \"{$schema}\".credit_cards(id) ON DELETE SET NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )");

        $this->seedDefaultCategories($schema);
    }

    private function seedDefaultCategories(string $schema): void
    {
        $now = now()->toDateTimeString();

        $parents = [
            ['name' => 'Moradia',       'type' => 'expense'],
            ['name' => 'Alimentação',   'type' => 'expense'],
            ['name' => 'Transporte',    'type' => 'expense'],
            ['name' => 'Saúde',         'type' => 'expense'],
            ['name' => 'Compras',       'type' => 'expense'],
            ['name' => 'Serviços',      'type' => 'expense'],
            ['name' => 'Eventos',       'type' => 'expense'],
            ['name' => 'Outros',        'type' => 'expense'],
            ['name' => 'Investimento',  'type' => 'expense'],
            ['name' => 'Receita',       'type' => 'income'],
        ];

        $children = [
            'Alimentação' => ['Bandeco', 'Lanchonete', 'Mercado', 'Restaurante'],
            'Transporte'  => ['Aplicativo', 'Ônibus', 'Viagem'],
            'Saúde'       => ['Convênio', 'Drogaria'],
            'Compras'     => ['Entretenimento', 'Pessoal', 'Presente'],
            'Serviços'    => ['Assinatura', 'Prestado'],
            'Eventos'     => ['Delivery', 'Rolê'],
            'Receita'     => ['Salário', 'Freelance', 'Outros'],
        ];

        $parentIds = [];

        foreach ($parents as $parent) {
            $id = DB::selectOne(
                "INSERT INTO \"{$schema}\".categories (name, type, created_at, updated_at)
                 VALUES (?, ?, ?, ?) RETURNING id",
                [$parent['name'], $parent['type'], $now, $now]
            )->id;

            $parentIds[$parent['name']] = $id;
        }

        foreach ($children as $parentName => $subs) {
            $parentId = $parentIds[$parentName];
            foreach ($subs as $sub) {
                DB::statement(
                    "INSERT INTO \"{$schema}\".categories (name, parent_id, type, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?)",
                    [$sub, $parentId, 'expense', $now, $now]
                );
            }
        }
    }
}
