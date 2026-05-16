<?php

namespace App\Modules\Family\Services;

use App\Models\Family;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FamilyService
{
    public function getFamily(User $user): Family
    {
        return $user->family->load('members');
    }

    public function invite(User $inviter, array $data): Invitation
    {
        if (! $inviter->hasRole('admin')) {
            abort(403, 'Somente administradores podem convidar membros.');
        }

        $family = $inviter->family;

        if ($family->members()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Este e-mail já é membro da família.'],
            ]);
        }

        $existing = Invitation::where('family_id', $family->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['Já existe um convite pendente para este e-mail.'],
            ]);
        }

        $invitation = Invitation::updateOrCreate(
            ['family_id' => $family->id, 'email' => $data['email']],
            [
                'invited_by' => $inviter->id,
                'token'      => Str::random(64),
                'role'       => $data['role'] ?? 'member',
                'accepted_at' => null,
                'expires_at' => now()->addDays(7),
            ]
        );

        // TODO: enviar e-mail de convite
        // Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return $invitation;
    }

    public function removeMember(User $admin, int $memberId): void
    {
        if (! $admin->hasRole('admin')) {
            abort(403, 'Somente administradores podem remover membros.');
        }

        $member = User::where('id', $memberId)
            ->where('family_id', $admin->family_id)
            ->firstOrFail();

        if ($member->id === $admin->id) {
            throw ValidationException::withMessages([
                'id' => ['Você não pode remover a si mesmo.'],
            ]);
        }

        $member->update(['family_id' => null]);
        $member->syncRoles([]);
    }

    public function updateRole(User $admin, int $memberId, string $role): User
    {
        if (! $admin->hasRole('admin')) {
            abort(403, 'Somente administradores podem alterar roles.');
        }

        $member = User::where('id', $memberId)
            ->where('family_id', $admin->family_id)
            ->firstOrFail();

        $member->syncRoles([$role]);

        return $member->fresh();
    }
}
