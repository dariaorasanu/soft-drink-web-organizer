<?php

class ShoppingListItem
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $listId,
        public readonly int     $productId,
        public readonly int     $quantity,
        public readonly ?string $notes,
        public readonly bool    $isPurchased,
        public readonly string  $addedAt,
        public readonly ?string $productName  = null,
        public readonly ?string $productImage = null,
        public readonly ?float  $productPrice = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:           (int)$row['id'],
            listId:       (int)$row['list_id'],
            productId:    (int)$row['product_id'],
            quantity:     (int)($row['quantity']     ?? 1),
            notes:        $row['notes']              ?? null,
            isPurchased:  (bool)($row['is_purchased'] ?? false),
            addedAt:      $row['added_at']            ?? '',
            productName:  $row['product_name']        ?? null,
            productImage: $row['image_url']           ?? null,
            productPrice: isset($row['price'])        ? (float)$row['price'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'list_id'       => $this->listId,
            'product_id'    => $this->productId,
            'quantity'      => $this->quantity,
            'notes'         => $this->notes,
            'is_purchased'  => $this->isPurchased,
            'added_at'      => $this->addedAt,
            'product_name'  => $this->productName,
            'product_price' => $this->productPrice,
        ];
    }
}