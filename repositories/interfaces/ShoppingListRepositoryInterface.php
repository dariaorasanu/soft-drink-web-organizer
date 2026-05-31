<?php

interface ShoppingListRepositoryInterface
{
    //liste
    public function findAllByUser(int $userId): array;
    public function findById(int $listId): ?ShoppingList;
    public function findByToken(string $token): ?ShoppingList;
    public function create(int $userId, string $name): int;
    public function rename(int $listId, string $name): bool;
    public function delete(int $listId): bool;
    public function enableShare(int $listId): string;
    public function disableShare(int $listId): bool;
    public function touchList(int $listId): void;

    //iteme
    public function findItemsByList(int $listId): array;
    public function findItemById(int $itemId): ?ShoppingListItem;
    public function itemExistsInList(int $listId, int $productId): ?int;
    public function addItem(int $listId, int $productId, int $quantity, ?string $notes): int;
    public function updateItem(int $itemId, int $quantity, ?string $notes): bool;
    public function removeItem(int $itemId): bool;
    public function markPurchased(int $itemId, bool $purchased): bool;
    public function clearPurchased(int $listId): int;
}