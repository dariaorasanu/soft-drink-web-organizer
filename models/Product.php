<?php

class Product
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $description,
        public readonly ?float  $price,
        public readonly ?string $imageUrl,
        public readonly ?string $ingredients,
        public readonly ?string $barcode,
        public readonly ?string $brand,
        public readonly ?int    $volumeMl,
        public readonly ?float  $caloriesPer100ml,
        public readonly ?float  $sugarPer100ml,
        public readonly bool    $isPerishable,
        public readonly ?int    $shelfLifeDays,
        public readonly bool    $isVegan,
        public readonly bool    $isGlutenFree,
        public readonly ?string $openfoodfactsId,
        public readonly int     $viewCount,
        public readonly ?int    $createdBy,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:               (int)$row['id'],
            name:             $row['name'],
            slug:             $row['slug'],
            description:      $row['description']              ?? null,
            price:            isset($row['price'])              ? (float)$row['price'] : null,
            imageUrl:         $row['image_url']                 ?? null,
            ingredients:      $row['ingredients']               ?? null,
            barcode:          $row['barcode']                   ?? null,
            brand:            $row['brand']                     ?? null,
            volumeMl:         isset($row['volume_ml'])          ? (int)$row['volume_ml'] : null,
            caloriesPer100ml: isset($row['calories_per_100ml']) ? (float)$row['calories_per_100ml'] : null,
            sugarPer100ml:    isset($row['sugar_per_100ml'])    ? (float)$row['sugar_per_100ml'] : null,
            isPerishable:     (bool)($row['is_perishable']      ?? false),
            shelfLifeDays:    isset($row['shelf_life_days'])    ? (int)$row['shelf_life_days'] : null,
            isVegan:          (bool)($row['is_vegan']           ?? false),
            isGlutenFree:     (bool)($row['is_gluten_free']     ?? false),
            openfoodfactsId:  $row['openfoodfacts_id']          ?? null,
            viewCount:        (int)($row['view_count']          ?? 0),
            createdBy:        isset($row['created_by'])         ? (int)$row['created_by'] : null,
            createdAt:        $row['created_at']                ?? '',
            updatedAt:        $row['updated_at']                ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'description'        => $this->description,
            'price'              => $this->price,
            'image_url'          => $this->imageUrl,
            'ingredients'        => $this->ingredients,
            'brand'              => $this->brand,
            'volume_ml'          => $this->volumeMl,
            'calories_per_100ml' => $this->caloriesPer100ml,
            'sugar_per_100ml'    => $this->sugarPer100ml,
            'is_perishable'      => $this->isPerishable,
            'shelf_life_days'    => $this->shelfLifeDays,
            'is_vegan'           => $this->isVegan,
            'is_gluten_free'     => $this->isGlutenFree,
            'view_count'         => $this->viewCount,
            'created_at'         => $this->createdAt,
        ];
    }
}