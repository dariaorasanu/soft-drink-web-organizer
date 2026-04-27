<?php

require_once __DIR__ . '/Interfaces/UserRepositoryInterface.php';
require_once __DIR__ . '/../models/User.php';

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $db) {}

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");

        return array_map(
            fn($row) => User::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        return $row ? User::fromArray($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, role, avatar_url)
            VALUES (:username, :email, :password_hash, :role, :avatar_url)
            RETURNING id
        ");

        $stmt->execute([
            ':username'      => $data['username'],
            ':email'         => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role'          => $data['role']       ?? 'user',
            ':avatar_url'    => $data['avatar_url'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET
                username   = :username,
                email      = :email,
                role       = :role,
                avatar_url = :avatar_url,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'         => $id,
            ':username'   => $data['username'],
            ':email'      => $data['email'],
            ':role'       => $data['role'],
            ':avatar_url' => $data['avatar_url'] ?? null,
        ]);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id
        ");

        return $stmt->execute([':id' => $id, ':hash' => $hash]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countAll(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    // favorites
    public function addFavorite(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_favorites (user_id, product_id)
            VALUES (:user_id, :product_id)
            ON CONFLICT DO NOTHING
        ");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    }

    public function removeFavorite(int $userId, int $productId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM user_favorites WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    }

    public function getFavorites(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.* FROM products p
            JOIN user_favorites uf ON uf.product_id = p.id
            WHERE uf.user_id = :user_id
            ORDER BY uf.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM user_favorites WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);

        return $stmt->fetch() !== false;
    }
}