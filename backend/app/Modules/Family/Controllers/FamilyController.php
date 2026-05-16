<?php

namespace App\Modules\Family\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Family\Requests\InviteRequest;
use App\Modules\Family\Requests\UpdateRoleRequest;
use App\Modules\Family\Services\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function __construct(private FamilyService $service) {}

    public function show(Request $request): JsonResponse
    {
        $family = $this->service->getFamily($request->user());

        return response()->json([
            'data'    => $family,
            'message' => 'OK',
            'status'  => 200,
        ]);
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        $invitation = $this->service->invite($request->user(), $request->validated());

        return response()->json([
            'data'    => $invitation,
            'message' => 'Convite enviado com sucesso.',
            'status'  => 201,
        ], 201);
    }

    public function removeMember(Request $request, int $id): JsonResponse
    {
        $this->service->removeMember($request->user(), $id);

        return response()->json([
            'data'    => null,
            'message' => 'Membro removido com sucesso.',
            'status'  => 200,
        ]);
    }

    public function updateRole(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $member = $this->service->updateRole($request->user(), $id, $request->validated('role'));

        return response()->json([
            'data'    => $member,
            'message' => 'Role atualizada com sucesso.',
            'status'  => 200,
        ]);
    }
}
