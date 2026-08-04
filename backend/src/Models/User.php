<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Data access layer for the users table (authentication).
 */
final class User
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE api_token = :token');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Create a user and return the stored row.
     *
     * @return array<string, mixed>
     */
    public function create(string $name, string $email, string $passwordHash): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash)
             VALUES (:name, :email, :password_hash)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    /** Issue a fresh bearer token for a user. */
    public function setToken(int $id, string $token): void
    {
        $stmt = $this->db->prepare('UPDATE users SET api_token = :token WHERE id = :id');
        $stmt->execute(['token' => $token, 'id' => $id]);
    }

    public function clearToken(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET api_token = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setRole(int $id, string $role): bool
    {
        $allowed = ['admin', 'guest'];
        if (!in_array($role, $allowed, true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Build a clean public representation of a user.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'] ?? 'guest',
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}