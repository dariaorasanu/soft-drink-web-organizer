<?php

require_once __DIR__ . '/../service/ShoppingListService.php';

class ShoppingListController
{
    public function __construct(
        private readonly ShoppingListService $service
    ) {}

    public function myLists(int $userId): void
    {
        $rows  = $this->service->getAllByUser($userId);
        $lists = array_map(fn(array $r) => $this->serializeList($r['list'], $r['item_count']), $rows);
        $this->jsonSuccess($lists);
    }


    public function create(int $userId): void
    {
        $name   = trim($_POST['name']   ?? '');
        $mood   = trim($_POST['mood']   ?? 'general');
        $budget = isset($_POST['budget']) && $_POST['budget'] !== ''
            ? (float)$_POST['budget'] : null;
        try {
            $list = $this->service->create($userId, $name, $mood, $budget);
            $this->jsonSuccess($this->serializeList($list, 0), 'Lista creată.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }

    public function rename(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        $name   = trim($_POST['name']     ?? '');
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $this->service->rename($listId, $userId, $name);
            $this->jsonSuccess(null, 'Lista redenumită.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function delete(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $this->service->delete($listId, $userId);
            $this->jsonSuccess(null, 'Lista ștearsă.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function setBudget(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        $budget = isset($_POST['budget']) && $_POST['budget'] !== ''
            ? (float)$_POST['budget'] : null;

        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $this->service->setBudget($listId, $userId, $budget);
            $this->jsonSuccess(['budget' => $budget], 'Buget actualizat.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function setMood(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        $mood   = trim($_POST['mood']     ?? 'general');
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $this->service->setMood($listId, $userId, $mood);
            $this->jsonSuccess(['mood' => $mood], 'Mood actualizat.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }

    public function share(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }

        try {
            $token = $this->service->share($listId, $userId);
            $this->jsonSuccess(['share_token' => $token], 'Partajare activată.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }

    public function unshare(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $this->service->unshare($listId, $userId);
            $this->jsonSuccess(null, 'Partajare dezactivată.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }

    public function sharedView(): void
    {
        $token = trim($_GET['token'] ?? '');
        try {
            $list  = $this->service->getSharedList($token);
            $items = $this->service->getSharedItems($list->id);
            $this->jsonSuccess([
                'list'  => $this->serializeList($list, count($items)),
                'items' => array_map(fn($i) => $this->serializeItem($i), $items),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 404);
        }
    }


    public function sharedMark(): void
    {
        $token     = trim($_POST['token']     ?? '');
        $itemId    = (int)($_POST['item_id']  ?? 0);
        $purchased = (bool)(int)($_POST['purchased'] ?? 0);
        if ($itemId <= 0) {
            $this->jsonError('item_id invalid.');
            return;
        }
        try {
            $this->service->markPurchasedShared($token, $itemId, $purchased);
            $this->jsonSuccess(['is_purchased' => $purchased], 'Stare actualizată.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function items(int $userId): void
    {
        $listId = (int)($_GET['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }
        try {
            $items = $this->service->getItems($listId, $userId);
            $this->jsonSuccess(array_map(fn($i) => $this->serializeItem($i), $items));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function addItem(int $userId): void
    {
        $listId    = (int)($_POST['list_id']    ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $notes     = trim($_POST['notes'] ?? '') ?: null;

        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }

        try {
            $item = $this->service->addItem($listId, $userId, $productId, $quantity, $notes);
            $this->jsonSuccess($this->serializeItem($item), 'Produs adăugat.');
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function removeItem(int $userId): void
    {
        $itemId = (int)($_POST['item_id'] ?? 0);

        if ($itemId <= 0) {
            $this->jsonError('item_id invalid.');
            return;
        }

        try {
            $this->service->removeItem($itemId, $userId);
            $this->jsonSuccess(null, 'Item șters.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function updateItem(int $userId): void
    {
        $itemId   = (int)($_POST['item_id']  ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $notes    = trim($_POST['notes'] ?? '') ?: null;
        if ($itemId <= 0) {
            $this->jsonError('item_id invalid.');
            return;
        }
        try {
            $item = $this->service->updateItem($itemId, $userId, $quantity, $notes);
            $this->jsonSuccess($this->serializeItem($item), 'Item actualizat.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function markPurchased(int $userId): void
    {
        $itemId    = (int)($_POST['item_id']    ?? 0);
        $purchased = (bool)(int)($_POST['purchased'] ?? 0);
        if ($itemId <= 0) {
            $this->jsonError('item_id invalid.');
            return;
        }
        try {
            $this->service->markPurchased($itemId, $userId, $purchased);
            $this->jsonSuccess(['is_purchased' => $purchased]);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    public function clearPurchased(int $userId): void
    {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) {
            $this->jsonError('list_id invalid.');
            return;
        }

        try {
            $deleted = $this->service->clearPurchased($listId, $userId);
            $this->jsonSuccess(['deleted_count' => $deleted], "$deleted item(e) șterse.");
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }


    private function serializeList(ShoppingList $list, int $itemCount = 0): array
    {
        return [
            'id'          => $list->id,
            'name'        => htmlspecialchars($list->name, ENT_QUOTES, 'UTF-8'),
            'is_shared'   => $list->isShared,
            'share_token' => $list->shareToken,
            'created_at'  => $list->createdAt,
            'updated_at'  => $list->updatedAt,
            'item_count'  => $itemCount,
            'budget'      => $list->budget,
            'mood'        => $list->mood,
        ];
    }


    private function serializeItem(ShoppingListItem $item): array
    {
        return [
            'id'            => $item->id,
            'list_id'       => $item->listId,
            'product_id'    => $item->productId,
            'quantity'      => $item->quantity,
            'notes'         => $item->notes !== null
                ? htmlspecialchars($item->notes, ENT_QUOTES, 'UTF-8')
                : null,
            'is_purchased'  => $item->isPurchased,
            'added_at'      => $item->addedAt,
            'product_name'  => $item->productName !== null
                ? htmlspecialchars($item->productName, ENT_QUOTES, 'UTF-8')
                : null,
            'product_brand' => $item->productBrand !== null
                ? htmlspecialchars($item->productBrand, ENT_QUOTES, 'UTF-8')
                : null,
            'product_slug'  => $item->productSlug  ?? null,
            'product_image' => $item->productImage ?? null,
            'product_price' => $item->productPrice,
            // Calculăm totalul pe linie: preț × cantitate
            'line_total'    => $item->productPrice !== null
                ? round($item->productPrice * $item->quantity, 2)
                : null,
        ];
    }


    private function jsonSuccess(mixed $data, string $message = 'OK', int $status = 200): void
    {
        http_response_code($status);
        echo json_encode(
            ['success' => true, 'message' => $message, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }


    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(
            ['success' => false, 'message' => $message, 'data' => null],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}