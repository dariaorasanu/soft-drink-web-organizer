<?php

interface ProductRepositoryInterface
{
    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array;
    public function findById(int $id): ?Product;
    public function findBySlug(string $slug): ?Product;
    public function findTopViewed(int $limit = 10): array;
    public function countAll(array $filters = []): int;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function incrementViewCount(int $id): void;
    public function findCategories(int $productId): array;
    public function findAllergens(int $productId): array;
    public function syncCategories(int $productId, array $categoryIds): void;
    public function syncAllergens(int $productId, array $allergenIds): void;
}