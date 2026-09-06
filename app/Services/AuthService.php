<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user and return the user with a token.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => $data['password'],
            'experience_level' => $data['experience_level'] ?? null,
        ]);

        $user->refresh(); // ensure casts (role enum) are loaded from DB

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Attempt login and return the user with a token.
     *
     * @throws AuthenticationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        // Revoke all previous tokens for a clean login (optional — remove if multi-device needed)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Revoke the current request token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
