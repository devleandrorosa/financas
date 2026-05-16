<?php

namespace App\Modules\BankAccount\Services;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;

class BankAccountService
{
    public function list(): Collection
    {
        return BankAccount::orderBy('name')->get();
    }

    public function create(array $data): BankAccount
    {
        return BankAccount::create($data);
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        $account->update($data);
        return $account->fresh();
    }

    public function delete(BankAccount $account): void
    {
        $account->delete();
    }
}
