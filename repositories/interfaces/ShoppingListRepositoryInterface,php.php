<?php

interface ShoppingListRepositoryInterface
{
    public function findByUser(int $userId): array;
    public function findById(int $id): ?ShoppingList;
    public function findByShareToken(string $token): ?ShoppingList;
    public function create(int $userId, string $name): int;
    public function delete(int $id): bool;
    public function share(int $id): string;
    public function unshare(int $id): void;
    public function getItems(int $listId): array;
    public function addItem(int $listId, int $productId, int $quantity, ?string $notes): int;
    public function updateItem(int $itemId, int $quantity, ?string $notes): bool;
    public function removeItem(int $itemId): bool;
    public function markItemPurchased(int $itemId, bool $purchased): bool;
    public function clearPurchased(int $listId): void;
}