<?php

class ShoppingList
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $userId,
        public readonly string  $name,
        public readonly bool    $isShared,
        public readonly ?string $shareToken,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:         (int)$row['id'],
            userId:     (int)$row['user_id'],
            name:       $row['name'],
            isShared:   (bool)($row['is_shared'] ?? false),
            shareToken: $row['share_token'] ?? null,
            createdAt:  $row['created_at']  ?? '',
            updatedAt:  $row['updated_at']  ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->userId,
            'name'        => $this->name,
            'is_shared'   => $this->isShared,
            'share_token' => $this->shareToken,
            'created_at'  => $this->createdAt,
        ];
    }
}