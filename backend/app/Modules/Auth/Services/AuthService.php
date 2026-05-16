<?php

namespace App\Modules\Auth\Services;

use App\Core\Services\TenantProvisioningService;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private TenantProvisioningService $provisioner
    ) {}

    public function register(array $data): array
    {
        $slug = Str::slug($data['family_name']) . '-' . Str::random(6);

        $family = Family::create([
            'name' => $data['family_name'],
            'slug' => $slug,
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'family_id' => $family->id,
        ]);

        $user->assignRole('admin');

        $this->provisioner->provision($slug);

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'         => $user->load('family'),
            'token'        => $token,
            'family_slug'  => $slug,
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'        => $user->load('family'),
            'token'       => $token,
            'family_slug' => $user->family?->slug,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function acceptInvite(array $data): array
    {
        $invitation = Invitation::where('token', $data['token'])->firstOrFail();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['Convite expirado ou já utilizado.'],
            ]);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $invitation->email,
            'password'  => Hash::make($data['password']),
            'family_id' => $invitation->family_id,
        ]);

        $user->assignRole($invitation->role);

        $invitation->update(['accepted_at' => now()]);

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'        => $user->load('family'),
            'token'       => $token,
            'family_slug' => $invitation->family->slug,
        ];
    }
}
