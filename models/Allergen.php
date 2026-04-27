<?php

class Allergen
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $icon,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:          (int)$row['id'],
            name:        $row['name'],
            description: $row['description'] ?? null,
            icon:        $row['icon']        ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'icon'        => $this->icon,
        ];
    }
}