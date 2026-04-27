<?php

class User
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $username,
        public readonly string  $email,
        public readonly string  $passwordHash,
        public readonly string  $role,
        public readonly ?string $avatarUrl,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id:           (int)$row['id'],
            username:     $row['username'],
            email:        $row['email'],
            passwordHash: $row['password_hash'],
            role:         $row['role'],
            avatarUrl:    $row['avatar_url'] ?? null,
            createdAt:    $row['created_at'] ?? '',
            updatedAt:    $row['updated_at'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'email'      => $this->email,
            'role'       => $this->role,
            'avatar_url' => $this->avatarUrl,
            'created_at' => $this->createdAt,
        ];
    }
}