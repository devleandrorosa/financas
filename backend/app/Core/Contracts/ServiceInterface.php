<?php

namespace App\Core\Contracts;

interface ServiceInterface
{
    public function all(): mixed;
    public function find(int $id): mixed;
    public function create(array $data): mixed;
    public function update(int $id, array $data): mixed;
    public function delete(int $id): bool;
}
