<?php

class Category
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $description,
        public readonly ?string $icon,
        public readonly ?string $color,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:          (int)$row['id'],
            name:        $row['name'],
            slug:        $row['slug'],
            description: $row['description'] ?? null,
            icon:        $row['icon']        ?? null,
            color:       $row['color']       ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'icon'        => $this->icon,
            'color'       => $this->color,
        ];
    }
}