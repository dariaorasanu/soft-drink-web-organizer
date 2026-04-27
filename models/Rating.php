<?php

class Rating
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $userId,
        public readonly int     $productId,
        public readonly int     $rating,
        public readonly ?string $review,
        public readonly string  $createdAt,
        public readonly ?string $username = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:        (int)$row['id'],
            userId:    (int)$row['user_id'],
            productId: (int)$row['product_id'],
            rating:    (int)$row['rating'],
            review:    $row['review']     ?? null,
            createdAt: $row['created_at'] ?? '',
            username:  $row['username']   ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->userId,
            'product_id' => $this->productId,
            'rating'     => $this->rating,
            'review'     => $this->review,
            'created_at' => $this->createdAt,
            'username'   => $this->username,
        ];
    }
}