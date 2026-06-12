<?php

class AdminRepository
{
    public function __construct(private readonly PDO $db) {}

    public function getStats(): array
    {
        return [
            'total_products'  => (int)$this->db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'total_users'     => (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_lists'     => (int)$this->db->query("SELECT COUNT(*) FROM shopping_lists")->fetchColumn(),
            'total_ratings'   => (int)$this->db->query("SELECT COUNT(*) FROM product_ratings")->fetchColumn(),
            'total_favorites' => (int)$this->db->query("SELECT COUNT(*) FROM user_favorites")->fetchColumn(),
        ];
    }

    public function findUsersPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.username,
                u.email,
                u.role,
                u.avatar_url,
                u.created_at,
                COUNT(DISTINCT sl.id)         AS list_count,
                COUNT(DISTINCT uf.product_id) AS favorite_count
            FROM users u
            LEFT JOIN shopping_lists sl ON sl.user_id = u.id
            LEFT JOIN user_favorites  uf ON uf.user_id = u.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFormData(): array
    {
        $categories = $this->db
            ->query("SELECT id, name, slug FROM categories ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

        $allergens = $this->db
            ->query("SELECT id, name FROM allergens ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

        $seasons = $this->db
            ->query("SELECT id, name FROM seasons ORDER BY id")
            ->fetchAll(PDO::FETCH_ASSOC);

        $regions = $this->db
            ->query("SELECT id, name, country FROM regions ORDER BY country, name")
            ->fetchAll(PDO::FETCH_ASSOC);

        return compact('categories', 'allergens', 'seasons', 'regions');
    }
}