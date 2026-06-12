<?php

interface AdminRepositoryInterface
{
    public function getStats(): array;
    public function findUsersPaginated(int $limit, int $offset): array;
    public function getFormData(): array;
}