<?php

class ShoppingListRepository
{
    public function __construct(private readonly PDO $pdo) {}

    //returneaza toate listele unui user cu numarul de iteme
    public function findAllByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sl.*,
                    COUNT(sli.id) AS item_count
             FROM   shopping_lists sl
             LEFT JOIN shopping_list_items sli ON sli.list_id = sl.id
             WHERE  sl.user_id = :uid
             GROUP  BY sl.id
             ORDER  BY sl.updated_at DESC'
        );
        $stmt->execute([':uid' => $userId]);

        $lists = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $list    = ShoppingList::fromArray($row);
            $lists[] = ['list' => $list, 'item_count' => (int)$row['item_count']];
        }
        return $lists;
    }

    //gaseste o lista dupa id
    public function findById(int $listId): ?ShoppingList
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM shopping_lists WHERE id = :id'
        );
        $stmt->execute([':id' => $listId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ShoppingList::fromArray($row) : null;
    }

    //gaseste o lista dupa share_token (fara autentificare)
    public function findByToken(string $token): ?ShoppingList
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM shopping_lists WHERE share_token = :token AND is_shared = TRUE'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ShoppingList::fromArray($row) : null;
    }

    //creeaza o lista noua cu mood si buget optional, returneaza id-ul
    public function create(int $userId, string $name, string $mood = 'general', ?float $budget = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO shopping_lists (user_id, name, mood, budget, created_at, updated_at)
             VALUES (:uid, :name, :mood, :budget, NOW(), NOW())
             RETURNING id'
        );
        $stmt->execute([':uid' => $userId, ':name' => $name, ':mood' => $mood, ':budget' => $budget]);
        return (int)$stmt->fetchColumn();
    }

    //redenumeste o lista
    public function rename(int $listId, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_lists SET name = :name, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':name' => $name, ':id' => $listId]);
    }

    //sterge o lista (CASCADE sterge si itemele)
    public function delete(int $listId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM shopping_lists WHERE id = :id');
        return $stmt->execute([':id' => $listId]);
    }

    //activeaza partajarea, generam un token unic de 48 caractere
    public function enableShare(int $listId): string
    {
        $token = bin2hex(random_bytes(24));
        $stmt  = $this->pdo->prepare(
            'UPDATE shopping_lists
             SET is_shared = TRUE, share_token = :token, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([':token' => $token, ':id' => $listId]);
        return $token;
    }

    //dezactiveaza partajarea si sterge tokenul
    public function disableShare(int $listId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_lists
             SET is_shared = FALSE, share_token = NULL, updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([':id' => $listId]);
    }

    //actualizam mereu updated_at la orice modificare de item
    public function touchList(int $listId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_lists SET updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':id' => $listId]);
    }

    //seteaza bugetul unei liste (null = sterge bugetul)
    public function setBudget(int $listId, ?float $budget): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_lists SET budget = :budget, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':budget' => $budget, ':id' => $listId]);
    }

    //seteaza mood-ul unei liste, verifica ca e o valoare valida
    public function setMood(int $listId, string $mood): bool
    {
        $allowed = ['general', 'picnic', 'acasa', 'petrecere', 'sport', 'birou'];
        if (!in_array($mood, $allowed, true)) return false;
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_lists SET mood = :mood, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([':mood' => $mood, ':id' => $listId]);
    }

    // ── Iteme ──────────────────────────────────────────────────────────

    //returneaza itemele unei liste cu datele produsului join-uite
    public function findItemsByList(int $listId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sli.*,
                    p.name       AS product_name,
                    p.brand      AS product_brand,
                    p.slug       AS product_slug,
                    p.image_url,
                    p.price,
                    p.is_vegan,
                    p.is_gluten_free
             FROM   shopping_list_items sli
             JOIN   products p ON p.id = sli.product_id
             WHERE  sli.list_id = :lid
             ORDER  BY sli.added_at ASC'
        );
        $stmt->execute([':lid' => $listId]);
        return array_map(
            fn(array $row) => ShoppingListItem::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    //gaseste un singur item dupa id
    public function findItemById(int $itemId): ?ShoppingListItem
    {
        $stmt = $this->pdo->prepare(
            'SELECT sli.*, p.name AS product_name, p.image_url, p.price
             FROM   shopping_list_items sli
             JOIN   products p ON p.id = sli.product_id
             WHERE  sli.id = :id'
        );
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ShoppingListItem::fromArray($row) : null;
    }

    //verifica daca produsul e deja in lista ca sa evitam duplicate
    public function itemExistsInList(int $listId, int $productId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM shopping_list_items
             WHERE list_id = :lid AND product_id = :pid'
        );
        $stmt->execute([':lid' => $listId, ':pid' => $productId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    //adaugam un item nou in lista
    public function addItem(int $listId, int $productId, int $quantity, ?string $notes): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO shopping_list_items (list_id, product_id, quantity, notes, added_at)
             VALUES (:lid, :pid, :qty, :notes, NOW())
             RETURNING id'
        );
        $stmt->execute([
            ':lid'   => $listId,
            ':pid'   => $productId,
            ':qty'   => max(1, $quantity),
            ':notes' => $notes,
        ]);
        return (int)$stmt->fetchColumn();
    }

    //actualizam cantitatea si/sau notita unui item
    public function updateItem(int $itemId, int $quantity, ?string $notes): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_list_items
             SET quantity = :qty, notes = :notes
             WHERE id = :id'
        );
        return $stmt->execute([':qty' => max(1, $quantity), ':notes' => $notes, ':id' => $itemId]);
    }

    //marcheaza sau demarcheaza un item ca cumparat
    public function markPurchased(int $itemId, bool $purchased): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE shopping_list_items SET is_purchased = :p WHERE id = :id'
        );
        return $stmt->execute([':p' => $purchased ? 'true' : 'false', ':id' => $itemId]);
    }

    //sterge un item
    public function removeItem(int $itemId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM shopping_list_items WHERE id = :id');
        return $stmt->execute([':id' => $itemId]);
    }

    //sterge toate itemele marcate ca cumparate dintr-o lista
    public function clearPurchased(int $listId): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM shopping_list_items
             WHERE list_id = :lid AND is_purchased = TRUE'
        );
        $stmt->execute([':lid' => $listId]);
        return $stmt->rowCount();
    }
}