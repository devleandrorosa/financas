<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\AcceptInviteRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private AuthService $service) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->service->register($request->validated());

        return response()->json([
            'data'    => $result,
            'message' => 'Conta criada com sucesso.',
            'status'  => 201,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());

        return response()->json([
            'data'    => $result,
            'message' => 'Login realizado com sucesso.',
            'status'  => 200,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user());
        Auth::guard('sanctum')->forgetUser();

        return response()->json([
            'data'    => null,
            'message' => 'Logout realizado com sucesso.',
            'status'  => 200,
        ]);
    }

    public function acceptInvite(AcceptInviteRequest $request): JsonResponse
    {
        $result = $this->service->acceptInvite($request->validated());

        return response()->json([
            'data'    => $result,
            'message' => 'Convite aceito com sucesso.',
            'status'  => 201,
        ], 201);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return response()->json([
            'data'    => $user->fresh(),
            'message' => 'Perfil atualizado.',
            'status'  => 200,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Senha atual incorreta.',
                'errors'  => ['current_password' => ['Senha atual incorreta.']],
                'status'  => 422,
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'data'    => null,
            'message' => 'Senha alterada com sucesso.',
            'status'  => 200,
        ]);
    }
}
