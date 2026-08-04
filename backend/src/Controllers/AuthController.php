<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * HTTP handlers for registration, login, logout and the current user.
 */
final class AuthController
{
    private User $model;

    public function __construct(?User $model = null)
    {
        $this->model = $model ?? new User();
    }

    public function register(Request $request): Response
    {
        $data = $request->jsonBody();
        $errors = $this->validateRegistration($data);

        if ($errors !== []) {
            return Response::json(['error' => 'Validation failed', 'fields' => $errors], 422);
        }

        $email = strtolower(trim($data['email']));

        if ($this->model->findByEmail($email) !== null) {
            return Response::json(
                ['error' => 'Validation failed', 'fields' => ['email' => 'An account with this email already exists.']],
                422
            );
        }

        $user = $this->model->create(
            trim($data['name']),
            $email,
            password_hash($data['password'], PASSWORD_DEFAULT)
        );

        $token = $this->issueToken($user['id']);

        return Response::json([
            'user' => User::present($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): Response
    {
        $data = $request->jsonBody();
        $errors = $this->validateCredentials($data);

        if ($errors !== []) {
            return Response::json(['error' => 'Validation failed', 'fields' => $errors], 422);
        }

        $email = strtolower(trim($data['email']));
        $user = $this->model->findByEmail($email);

        if ($user === null || !password_verify($data['password'], $user['password_hash'])) {
            return Response::json(['error' => 'Invalid email or password.'], 401);
        }

        $token = $this->issueToken((int) $user['id']);

        return Response::json([
            'user' => User::present($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        if ($user !== null) {
            $this->model->clearToken((int) $user['id']);
        }

        return Response::json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            return Response::json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        return Response::json(['user' => User::present($user)]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateRegistration(array $data): array
    {
        $errors = $this->validateCredentials($data);

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'Name must be 255 characters or fewer.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateCredentials(array $data): array
    {
        $errors = [];

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        $password = (string) ($data['password'] ?? '');
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 255) {
            $errors['password'] = 'Password must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->model->setToken($userId, $token);
        return $token;
    }
}