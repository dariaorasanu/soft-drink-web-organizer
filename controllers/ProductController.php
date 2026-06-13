<?php

require_once __DIR__ . '/../service/ProductService.php';
require_once __DIR__ . '/../service/UserService.php';

class ProductController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly UserService    $userService
    ) {}

    public function list(): void
    {
        try {
            $page   = max(1, (int)($_GET['page']  ?? 1));
            $limit  = max(1, min((int)($_GET['limit'] ?? 12), 50));
            $offset = ($page - 1) * $limit;

            $filters = [
                'category_id'   => $_GET['category_id']  ?? null,
                'category'      => $_GET['category']      ?? null,
                'season'        => $_GET['season']        ?? null,
                'region'        => $_GET['region']        ?? null,
                'is_vegan'      => $_GET['vegan']         ?? null,
                'is_gluten_free'=> $_GET['gluten_free']   ?? null,
                'search'        => $_GET['q']             ?? null,
                'brand'         => $_GET['brand']         ?? null,
            ];

            // citim userul din JWT — null dacă nu e logat
            $user   = $this->userService->getCurrentUser();
            $userId = $user?->id;

            $result = $this->productService->getAll($filters, $limit, $offset, $userId);

            $this->jsonSuccess([
                'products'   => $result['products'],
                'pagination' => [
                    'page'        => $page,
                    'limit'       => $result['limit'],
                    'offset'      => $result['offset'],
                    'total'       => $result['total'],
                    'total_pages' => (int)ceil($result['total'] / $result['limit']),
                ],
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la încărcarea produselor.', 500);
        }
    }

    public function get(): void
    {
        try {
            $product = null;

            if (!empty($_GET['id'])) {
                $product = $this->productService->getById((int)$_GET['id']);
            } elseif (!empty($_GET['slug'])) {
                $product = $this->productService->getBySlug((string)$_GET['slug']);
            }

            if ($product === null) {
                $this->jsonError('Produsul nu a fost găsit.', 404);
                return;
            }

            $this->jsonSuccess(['product' => $product]);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la încărcarea produsului.', 500);
        }
    }

    public function top(): void
    {
        try {
            $limit    = max(1, min((int)($_GET['limit'] ?? 10), 20));
            $products = $this->productService->getTopViewed($limit);
            $this->jsonSuccess(['products' => $products]);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la încărcarea topului.', 500);
        }
    }

    public function search(): void
    {
        $_GET['q'] = $_GET['q'] ?? '';
        $this->list();
    }

    public function toggleFavorite(): void
    {
        try {
            $user = $this->userService->getCurrentUser();
            if ($user === null) {
                $this->jsonError('Trebuie să fii autentificat.', 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Metodă invalidă.', 405);
                return;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId <= 0) {
                $this->jsonError('Produs invalid.', 400);
                return;
            }

            $result = $this->productService->toggleFavorite($user->id, $productId);
            $this->jsonSuccess($result);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la actualizarea favoritului.', 500);
        }
    }

    public function rate(): void
    {
        try {
            // verificăm autentificarea via JWT
            $user = $this->userService->getCurrentUser();
            if ($user === null) {
                $this->jsonError('Trebuie să fii autentificat.', 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Metodă invalidă.', 405);
                return;
            }

            $productId = (int)($_POST['product_id'] ?? 0);
            $rating    = (int)($_POST['rating']     ?? 0);
            $review    = $_POST['review']           ?? null;

            if ($productId <= 0) {
                $this->jsonError('Produs invalid.', 400);
                return;
            }

            $result = $this->productService->addRating($user->id, $productId, $rating, $review);
            $this->jsonSuccess($result);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la salvarea ratingului.', 500);
        }
    }

    public function getRatings(): void
    {
        try {
            $productId = (int)($_GET['product_id'] ?? 0);
            if ($productId <= 0) {
                $this->jsonError('Produs invalid.', 400);
                return;
            }

            $ratings = $this->productService->getRatings($productId);
            $this->jsonSuccess(['ratings' => $ratings]);

        } catch (Throwable $e) {
            $this->jsonError('Eroare la încărcarea ratingurilor.', 500);
        }
    }

    public function incrementView(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->jsonError('product_id invalid.');
            return;
        }
        $this->productService->incrementView($productId);
        $this->jsonSuccess(['incremented' => true]);
    }

    public function create(): void
    {
        try {
            $user = $this->requireAdmin();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Metodă invalidă.', 405);
                return;
            }

            $product = $this->productService->create($_POST, $user->id);
            $this->jsonSuccess(['product' => $product], 201);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la crearea produsului.', 500);
        }
    }

    public function update(): void
    {
        try {
            $this->requireAdmin();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Metodă invalidă.', 405);
                return;
            }

            $id      = (int)($_POST['id'] ?? 0);
            $product = $this->productService->update($id, $_POST);
            $this->jsonSuccess(['product' => $product]);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la actualizarea produsului.', 500);
        }
    }

    public function delete(): void
    {
        try {
            $this->requireAdmin();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Metodă invalidă.', 405);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $this->productService->delete($id);
            $this->jsonSuccess(['message' => 'Produs șters cu succes.']);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->jsonError('Eroare la ștergerea produsului.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // HELPERS PRIVATE
    // -------------------------------------------------------------------------

    /**
     * Verifică că userul e logat și are rol de admin via JWT.
     * Returnează obiectul User dacă e admin.
     */
    private function requireAdmin(): \User
    {
        $user = $this->userService->getCurrentUser();

        if ($user === null) {
            $this->jsonError('Trebuie să fii autentificat.', 401);
            exit;
        }

        if ($user->role !== 'admin') {
            $this->jsonError('Ai nevoie de rol de admin.', 403);
            exit;
        }

        return $user;
    }

    private function jsonSuccess(array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, ...$data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}