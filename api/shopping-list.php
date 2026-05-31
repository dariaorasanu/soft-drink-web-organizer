<?php

session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Bootstrap.php';
require_once __DIR__ . '/../models/ShopppingList.php';
require_once __DIR__ . '/../models/ShoppingListItem.php';
require_once __DIR__ . '/../repositories/ShoppingListRepository.php';

/** @var PDO        $pdo  */
/** @var AuthGuard  $guard */

header('Content-Type: application/json; charset=utf-8');

$repo   = new ShoppingListRepository($pdo);
$action = $_GET['action'] ?? '';

//facem json uniform, tot ce returnam e in format de json
function jsonOk(mixed $data = null, string $message = 'OK'): never
{
    echo json_encode(
        ['success' => true, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function jsonErr(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(
        ['success' => false, 'message' => $message, 'data' => null],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

//verifica ca lista ii apartine userului curent
function requireListOwner(ShoppingListRepository $repo, int $listId, int $userId): \ShoppingList
{
    $list = $repo->findById($listId);
    if ($list === null)          jsonErr('Lista nu există.', 404);
    if ($list->userId !== $userId) jsonErr('Acces interzis.', 403);
    return $list;
}

//verifica ca itemul apartine unei liste a userului curent
function requireItemOwner(ShoppingListRepository $repo, int $itemId, int $userId): \ShoppingListItem
{
    $item = $repo->findItemById($itemId);
    if ($item === null) jsonErr('Itemul nu există.', 404);
    requireListOwner($repo, $item->listId, $userId); // aruncă 403 dacă nu e owner
    return $item;
}

/** Serializează ShoppingList la array curat (XSS safe). */
//facem serializarea listei la un array curat
function serializeList(\ShoppingList $list, int $itemCount = 0): array
{
    return [
        'id'          => $list->id,
        'name'        => htmlspecialchars($list->name, ENT_QUOTES, 'UTF-8'),
        'is_shared'   => $list->isShared,
        'share_token' => $list->shareToken,
        'created_at'  => $list->createdAt,
        'updated_at'  => $list->updatedAt,
        'item_count'  => $itemCount,
    ];
}

//serializam si itemul la un array curat
function serializeItem(\ShoppingListItem $item): array
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
        'product_brand' => isset($item->productBrand)
            ? htmlspecialchars($item->productBrand, ENT_QUOTES, 'UTF-8')
            : null,
        'product_slug'  => $item->productSlug  ?? null,
        'product_image' => $item->productImage ?? null,
        'product_price' => $item->productPrice,
        'line_total'    => $item->productPrice !== null
            ? round($item->productPrice * $item->quantity, 2)
            : null,
    ];
}

//actiunea fara autentificare
if ($action === 'shared_view') {
    $token = trim($_GET['token'] ?? '');
    if ($token === '') jsonErr('Token lipsă.');

    $list = $repo->findByToken($token);
    if ($list === null) jsonErr('Lista nu există sau nu este partajată.', 404);

    $items     = $repo->findItemsByList($list->id);
    $itemsData = array_map('serializeItem', $items);

    jsonOk([
        'list'  => serializeList($list, count($items)),
        'items' => $itemsData,
    ]);
}


$guard->requireAuth();

// getCurrentUser() returneaza obiectul user sau null
$currentUser = $userService->getCurrentUser();
if ($currentUser === null) jsonErr('Neautentificat.', 401);
$userId = $currentUser->id;

match ($action) {
    //listele mele
    'my_lists' => (function () use ($repo, $userId): never {
        $rows  = $repo->findAllByUser($userId);
        $lists = array_map(
            fn(array $r) => serializeList($r['list'], $r['item_count']),
            $rows
        );
        jsonOk($lists);
    })(),

    //creeaza lista
    'create' => (function () use ($repo, $userId): never {
        $name = trim($_POST['name'] ?? '');
        if ($name === '')          jsonErr('Numele listei este obligatoriu.');
        if (mb_strlen($name) > 200) jsonErr('Numele este prea lung (max 200 caractere).');

        $listId = $repo->create($userId, $name);
        $list   = $repo->findById($listId);

        jsonOk(serializeList($list, 0), 'Listă creată.');
    })(),

    //redenumire
    'rename' => (function () use ($repo, $userId): never {
        $listId = (int)($_POST['list_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        if ($listId <= 0) jsonErr('list_id invalid.');
        if ($name === '')  jsonErr('Numele nu poate fi gol.');

        requireListOwner($repo, $listId, $userId);
        $repo->rename($listId, $name);

        jsonOk(null, 'Listă redenumită.');
    })(),

    //sterge lista
    'delete' => (function () use ($repo, $userId): never {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) jsonErr('list_id invalid.');

        requireListOwner($repo, $listId, $userId);
        $repo->delete($listId);

        jsonOk(null, 'Listă ștearsă.');
    })(),

    //itemele unei liste
    'items' => (function () use ($repo, $userId): never {
        $listId = (int)($_GET['list_id'] ?? 0);
        if ($listId <= 0) jsonErr('list_id invalid.');

        requireListOwner($repo, $listId, $userId);
        $items = $repo->findItemsByList($listId);

        jsonOk(array_map('serializeItem', $items));
    })(),

    //adaugam item
    'add_item' => (function () use ($repo, $userId): never {
        $listId    = (int)($_POST['list_id']    ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $notes     = trim($_POST['notes'] ?? '') ?: null;

        if ($listId <= 0)    jsonErr('list_id invalid.');
        if ($productId <= 0) jsonErr('product_id invalid.');

        requireListOwner($repo, $listId, $userId);

        // Dacă produsul e deja în listă, incrementăm cantitatea
        $existingId = $repo->itemExistsInList($listId, $productId);
        if ($existingId !== null) {
            $existing = $repo->findItemById($existingId);
            $repo->updateItem($existingId, $existing->quantity + $quantity, $notes ?? $existing->notes);
            $item = $repo->findItemById($existingId);
        } else {
            $itemId = $repo->addItem($listId, $productId, $quantity, $notes);
            $item   = $repo->findItemById($itemId);
        }

        $repo->touchList($listId);
        jsonOk(serializeItem($item), 'Produs adăugat.');
    })(),

    //stergem item
    'remove_item' => (function () use ($repo, $userId): never {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) jsonErr('item_id invalid.');

        $item = requireItemOwner($repo, $itemId, $userId);
        $repo->removeItem($itemId);
        $repo->touchList($item->listId);

        jsonOk(null, 'Item șters.');
    })(),

    //actualizam item
    'update_item' => (function () use ($repo, $userId): never {
        $itemId   = (int)($_POST['item_id']  ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $notes    = trim($_POST['notes'] ?? '') ?: null;

        if ($itemId <= 0) jsonErr('item_id invalid.');

        $item = requireItemOwner($repo, $itemId, $userId);
        $repo->updateItem($itemId, $quantity, $notes);
        $repo->touchList($item->listId);

        $updated = $repo->findItemById($itemId);
        jsonOk(serializeItem($updated), 'Item actualizat.');
    })(),

    //marcam ca cumparat
    'mark_purchased' => (function () use ($repo, $userId): never {
        $itemId    = (int)($_POST['item_id']    ?? 0);
        $purchased = (bool)(int)($_POST['purchased'] ?? 0);

        if ($itemId <= 0) jsonErr('item_id invalid.');

        $item = requireItemOwner($repo, $itemId, $userId);
        $repo->markPurchased($itemId, $purchased);
        $repo->touchList($item->listId);

        jsonOk(['is_purchased' => $purchased]);
    })(),

    //stergem tot ce am cumparat
    'clear_purchased' => (function () use ($repo, $userId): never {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) jsonErr('list_id invalid.');

        requireListOwner($repo, $listId, $userId);
        $deleted = $repo->clearPurchased($listId);
        $repo->touchList($listId);

        jsonOk(['deleted_count' => $deleted], "$deleted item(e) șterse.");
    })(),

    //activeaza partajarea
    'share' => (function () use ($repo, $userId): never {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) jsonErr('list_id invalid.');

        requireListOwner($repo, $listId, $userId);
        $token = $repo->enableShare($listId);

        jsonOk(['share_token' => $token], 'Partajare activată.');
    })(),

    //dezactiveaza partajarea
    'unshare' => (function () use ($repo, $userId): never {
        $listId = (int)($_POST['list_id'] ?? 0);
        if ($listId <= 0) jsonErr('list_id invalid.');

        requireListOwner($repo, $listId, $userId);
        $repo->disableShare($listId);

        jsonOk(null, 'Partajare dezactivată.');
    })(),

    //default nu exista
    default => jsonErr('Acțiune inexistentă.', 404),
};