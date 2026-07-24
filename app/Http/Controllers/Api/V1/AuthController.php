<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Auth\UserAuthenticationService;
use App\Services\CustomerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserAuthenticationService $authService,
        private readonly CustomerProfileService $customerProfiles,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        try {
            $this->authService->ensureMayLogin($user);
        } catch (ValidationException $exception) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [$exception->errors()['email'][0] ?? 'You cannot sign in with this account.'],
            ]);
        }

        $this->authService->recordSuccessfulLogin($user);

        $token = $user->createToken($request->input('device_name', 'api-token'));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'status' => 'active',
            'password' => Hash::make($request->string('password')->toString()),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');
        $this->customerProfiles->ensureForUser($user);

        $token = $user->createToken($request->input('device_name', 'api-token'));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
