<?php

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct(private array $payload)
    {
        parent::__construct($payload);
    }

    public function toArray(Request $request): array
    {
        return $this->payload;
    }
}
