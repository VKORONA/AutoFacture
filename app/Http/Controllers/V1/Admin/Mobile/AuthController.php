<?php

namespace Crater\Http\Controllers\V1\Admin\Mobile;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\LoginRequest;
use Crater\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $identifier = trim((string) $request->username);

        if (app()->environment(['local', 'testing']) && strcasecmp($identifier, 'admin') === 0) {
            $identifier = 'admin@autofacture.local';
        }

        $user = User::where('email', $identifier)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        return response()->json([
            'type' => 'Bearer',
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function check()
    {
        return Auth::check();
    }
}
