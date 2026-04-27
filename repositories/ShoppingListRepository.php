<?php

require_once __DIR__ . '/Interfaces/ShoppingListRepositoryInterface.php';
require_once __DIR__ . '/../models/ShoppingList.php';
require_once __DIR__ . '/../models/ShoppingListItem.php';

class ShoppingListRepository implements ShoppingListRepositoryInterface
{
    public function __construct(private PDO $db) {}

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT sl.*,
                   COUNT(sli.id) as item_count
            FROM shopping_lists sl
            LEFT JOIN shopping_list_items sli ON sli.list_id = sl.id
            WHERE sl.user_id = :user_id
            GROUP BY sl.id
            ORDER BY sl.updated_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);

        return array_map(
            fn($row) => ShoppingList::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function findById(int $id): ?ShoppingList
    {
        $stmt = $this->db->prepare("SELECT * FROM shopping_lists WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? ShoppingList::fromArray($row) : null;
    }

    public function findByShareToken(string $token): ?ShoppingList
    {
        $stmt = $this->db->prepare("
            SELECT * FROM shopping_lists WHERE share_token = :token AND is_shared = true
        ");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row ? ShoppingList::fromArray($row) : null;
    }

    public function create(int $userId, string $name): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO shopping_lists (user_id, name)
            VALUES (:user_id, :name)
            RETURNING id
        ");
        $stmt->execute([':user_id' => $userId, ':name' => $name]);

        return (int)$stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM shopping_lists WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // generează un token unic și activează partajarea
    public function share(int $id): string
    {
        $token = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare("
            UPDATE shopping_lists
            SET is_shared = true, share_token = :token, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':token' => $token, ':id' => $id]);

        return $token;
    }

    public function unshare(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE shopping_lists
            SET is_shared = false, share_token = NULL, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    // items

    public function getItems(int $listId): array
    {
        // join cu products ca să avem numele și prețul direct
        $stmt = $this->db->prepare("
            SELECT sli.*,
                   p.name  AS product_name,
                   p.image_url,
                   p.price,
                   p.brand
            FROM shopping_list_items sli
            JOIN products p ON p.id = sli.product_id
            WHERE sli.list_id = :list_id
            ORDER BY sli.is_purchased ASC, sli.added_at DESC
        ");
        $stmt->execute([':list_id' => $listId]);

        return array_map(
            fn($row) => ShoppingListItem::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function addItem(int $listId, int $productId, int $quantity = 1, ?string $notes = null): int
    {
        // dacă produsul există deja în listă, doar crește cantitatea
        $existing = $this->db->prepare("
            SELECT id, quantity FROM shopping_list_items
            WHERE list_id = :list_id AND product_id = :product_id
        ");
        $existing->execute([':list_id' => $listId, ':product_id' => $productId]);
        $row = $existing->fetch();

        if ($row) {
            $update = $this->db->prepare("
                UPDATE shopping_list_items SET quantity = :qty WHERE id = :id
            ");
            $update->execute([':qty' => $row['quantity'] + $quantity, ':id' => $row['id']]);
            return (int)$row['id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO shopping_list_items (list_id, product_id, quantity, notes)
            VALUES (:list_id, :product_id, :quantity, :notes)
            RETURNING id
        ");
        $stmt->execute([
            ':list_id'    => $listId,
            ':product_id' => $productId,
            ':quantity'   => $quantity,
            ':notes'      => $notes,
        ]);

        $this->touchList($listId);

        return (int)$stmt->fetchColumn();
    }

    public function updateItem(int $itemId, int $quantity, ?string $notes): bool
    {
        $stmt = $this->db->prepare("
            UPDATE shopping_list_items SET quantity = :qty, notes = :notes WHERE id = :id
        ");
        return $stmt->execute([':qty' => $quantity, ':notes' => $notes, ':id' => $itemId]);
    }

    public function removeItem(int $itemId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM shopping_list_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

    public function markItemPurchased(int $itemId, bool $purchased): bool
    {
        $stmt = $this->db->prepare("
            UPDATE shopping_list_items SET is_purchased = :purchased WHERE id = :id
        ");
        return $stmt->execute([':purchased' => $purchased, ':id' => $itemId]);
    }

    public function clearPurchased(int $listId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM shopping_list_items WHERE list_id = :list_id AND is_purchased = true
        ");
        $stmt->execute([':list_id' => $listId]);
    }

    // updatează updated_at pe listă când se modifică ceva
    private function touchList(int $listId): void
    {
        $stmt = $this->db->prepare("UPDATE shopping_lists SET updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $listId]);
    }
}