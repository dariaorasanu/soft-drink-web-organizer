<?php

require_once __DIR__ . '/../repositories/ShoppingListRepository.php';
require_once __DIR__ . '/../models/ShopppingList.php';
require_once __DIR__ . '/../models/ShoppingListItem.php';

class ShoppingListService
{
    //moodurile permise pentru o lista
    private const ALLOWED_MOODS = ['general', 'picnic', 'acasa', 'petrecere', 'sport', 'birou'];

    public function __construct(
        private readonly ShoppingListRepository $repo
    ) {}


    public function getAllByUser(int $userId): array
    {
        return $this->repo->findAllByUser($userId);
    }

    public function create(int $userId, string $name, string $mood = 'general', ?float $budget = null): ShoppingList
    {

        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Numele listei este obligatoriu.');
        }
        if (strlen($name) > 200) {
            throw new InvalidArgumentException('Numele este prea lung (max 200 caractere).');
        }

        if (!in_array($mood, self::ALLOWED_MOODS, true)) {
            $mood = 'general';
        }

        if ($budget !== null && $budget <= 0) {
            $budget = null;
        }

        $listId = $this->repo->create($userId, $name, $mood, $budget);
        return $this->repo->findById($listId);
    }


    public function rename(int $listId, int $userId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Numele nu poate fi gol.');
        }
        $this->requireOwner($listId, $userId);
        $this->repo->rename($listId, $name);
    }


    public function delete(int $listId, int $userId): void
    {
        $this->requireOwner($listId, $userId);
        $this->repo->delete($listId);
    }


    public function setBudget(int $listId, int $userId, ?float $budget): void
    {
        $this->requireOwner($listId, $userId);
        $this->repo->setBudget($listId, $budget > 0 ? $budget : null);
    }


    public function setMood(int $listId, int $userId, string $mood): void
    {
        $this->requireOwner($listId, $userId);
        if (!in_array($mood, self::ALLOWED_MOODS, true)) {
            $mood = 'general';
        }
        $this->repo->setMood($listId, $mood);
    }


    public function share(int $listId, int $userId): string
    {
        $this->requireOwner($listId, $userId);
        return $this->repo->enableShare($listId);
    }


    public function unshare(int $listId, int $userId): void
    {
        $this->requireOwner($listId, $userId);
        $this->repo->disableShare($listId);
    }


    public function getSharedList(string $token): ShoppingList
    {
        if (trim($token) === '') {
            throw new InvalidArgumentException('Token lipsă.');
        }
        $list = $this->repo->findByToken($token);
        if ($list === null) {
            throw new RuntimeException('Lista nu există sau nu este partajată.');
        }
        return $list;
    }


    public function getItems(int $listId, int $userId): array
    {
        $this->requireOwner($listId, $userId);
        return $this->repo->findItemsByList($listId);
    }


    public function getSharedItems(int $listId): array
    {
        return $this->repo->findItemsByList($listId);
    }


    public function addItem(int $listId, int $userId, int $productId, int $quantity = 1, ?string $notes = null): ShoppingListItem
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException('product_id invalid.');
        }

        $this->requireOwner($listId, $userId);

        $existingId = $this->repo->itemExistsInList($listId, $productId);
        if ($existingId !== null) {
            $existing = $this->repo->findItemById($existingId);
            $this->repo->updateItem(
                $existingId,
                $existing->quantity + $quantity,
                $notes ?? $existing->notes
            );
            $item = $this->repo->findItemById($existingId);
        } else {
            $itemId = $this->repo->addItem($listId, $productId, $quantity, $notes);
            $item   = $this->repo->findItemById($itemId);
        }
        $this->repo->touchList($listId);
        return $item;
    }


    public function removeItem(int $itemId, int $userId): void
    {
        $item = $this->requireItemOwner($itemId, $userId);
        $this->repo->removeItem($itemId);
        $this->repo->touchList($item->listId);
    }


    public function updateItem(int $itemId, int $userId, int $quantity, ?string $notes): ShoppingListItem
    {
        $item = $this->requireItemOwner($itemId, $userId);
        $this->repo->updateItem($itemId, $quantity, $notes);
        $this->repo->touchList($item->listId);
        return $this->repo->findItemById($itemId);
    }


    public function markPurchased(int $itemId, int $userId, bool $purchased): void
    {
        $item = $this->requireItemOwner($itemId, $userId);
        $this->repo->markPurchased($itemId, $purchased);
        $this->repo->touchList($item->listId);
    }


    public function markPurchasedShared(string $token, int $itemId, bool $purchased): void
    {
        $list = $this->getSharedList($token);
        $item = $this->repo->findItemById($itemId);
        if ($item === null) {
            throw new RuntimeException('Itemul nu există.');
        }
        if ($item->listId !== $list->id) {
            throw new RuntimeException('Itemul nu aparține acestei liste.');
        }
        $this->repo->markPurchased($itemId, $purchased);
        $this->repo->touchList($list->id);
    }


    public function clearPurchased(int $listId, int $userId): int
    {
        $this->requireOwner($listId, $userId);
        $deleted = $this->repo->clearPurchased($listId);
        $this->repo->touchList($listId);
        return $deleted;
    }


    private function requireOwner(int $listId, int $userId): ShoppingList
    {
        $list = $this->repo->findById($listId);
        if ($list === null) {
            throw new RuntimeException('Lista nu există.');
        }
        if ($list->userId !== $userId) {
            throw new RuntimeException('Acces interzis.');
        }
        return $list;
    }


    private function requireItemOwner(int $itemId, int $userId): ShoppingListItem
    {
        $item = $this->repo->findItemById($itemId);
        if ($item === null) {
            throw new RuntimeException('Itemul nu există.');
        }
        $this->requireOwner($item->listId, $userId);
        return $item;
    }
}