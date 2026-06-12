<?php

class OpenFoodFactsService
{
    private const BASE_URL = 'https://world.openfoodfacts.org';
    private const USER_AGENT = 'SoftDrinkOrganizer/1.0 (student project)';

    private const PRODUCT_FIELDS = [
        'code',
        '_id',
        'product_name',
        'product_name_ro',
        'product_name_en',
        'generic_name',
        'generic_name_ro',
        'generic_name_en',
        'brands',
        'quantity',
        'image_url',
        'image_front_url',
        'ingredients_text',
        'ingredients_text_ro',
        'ingredients_text_en',
        'categories',
        'categories_tags',
        'labels_tags',
        'ingredients_analysis_tags',
        'nutriments',
    ];

    public function searchByBarcode(string $barcode): ?array
    {
        $barcode = preg_replace('/\D+/', '', $barcode);

        if ($barcode === '') {
            return null;
        }

        $url = self::BASE_URL
            . '/api/v3/product/' . rawurlencode($barcode) . '.json'
            . '?fields=' . rawurlencode(implode(',', self::PRODUCT_FIELDS));

        $data = $this->getJson($url);

        return !empty($data['product']) && is_array($data['product'])
            ? $data['product']
            : null;
    }

    public function searchByName(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return [];
        }

        $params = http_build_query([
            'search_terms' => $name,
            'search_simple' => 1,
            'action' => 'process',
            'json' => 1,
            'page_size' => 10,
            'fields' => implode(',', self::PRODUCT_FIELDS),
        ]);

        $data = $this->getJson(self::BASE_URL . '/cgi/search.pl?' . $params);

        return !empty($data['products']) && is_array($data['products'])
            ? $data['products']
            : [];
    }

    public function mapToProduct(array $offData): array
    {
        $nutriments = is_array($offData['nutriments'] ?? null) ? $offData['nutriments'] : [];
        $barcode = (string)($offData['code'] ?? $offData['_id'] ?? '');

        return [
            'name' => $this->firstValue($offData, [
                'product_name_ro',
                'product_name_en',
                'product_name',
                'generic_name_ro',
                'generic_name_en',
                'generic_name',
            ]) ?: 'Produs Open Food Facts',

            'description' => $this->firstValue($offData, [
                'generic_name_ro',
                'generic_name_en',
                'generic_name',
                'categories',
            ]),

            'price' => null,

            'image_url' => $this->firstValue($offData, [
                'image_front_url',
                'image_url',
            ]),

            'ingredients' => $this->firstValue($offData, [
                'ingredients_text_ro',
                'ingredients_text_en',
                'ingredients_text',
            ]),

            'barcode' => $barcode,
            'brand' => $this->firstBrand($offData['brands'] ?? null),
            'volume_ml' => $this->extractVolumeMl($offData['quantity'] ?? null),
            'calories_per_100ml' => $this->firstNumber($nutriments, ['energy-kcal_100ml', 'energy-kcal_100g']),
            'sugar_per_100ml' => $this->firstNumber($nutriments, ['sugars_100ml', 'sugars_100g']),
            'is_perishable' => $this->hasAnyTag($offData['categories_tags'] ?? [], [
                'en:dairies',
                'en:milk',
                'en:yogurts',
                'en:kefirs',
                'en:smoothies',
            ]),
            'shelf_life_days' => null,
            'is_vegan' => $this->hasAnyTag($offData['labels_tags'] ?? [], ['en:vegan'])
                || $this->hasAnyTag($offData['ingredients_analysis_tags'] ?? [], ['en:vegan']),
            'is_gluten_free' => $this->hasAnyTag($offData['labels_tags'] ?? [], ['en:gluten-free']),
            'openfoodfacts_id' => $barcode,
        ];
    }

    private function getJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => implode("\r\n", [
                    'User-Agent: ' . self::USER_AGENT,
                    'Accept: application/json',
                ]),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    private function firstValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return trim($data[$key]);
            }
        }

        return null;
    }

    private function firstBrand(mixed $brands): ?string
    {
        if (!is_string($brands) || trim($brands) === '') {
            return null;
        }

        return trim(explode(',', $brands)[0]);
    }

    private function firstNumber(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float)$data[$key];
            }
        }

        return null;
    }

    private function extractVolumeMl(?string $quantity): ?int
    {
        if (!$quantity) {
            return null;
        }

        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(ml|cl|l)\b/i', $quantity, $matches)) {
            return null;
        }

        $amount = (float)str_replace(',', '.', $matches[1]);
        $unit = strtolower($matches[2]);

        return match ($unit) {
            'l' => (int)round($amount * 1000),
            'cl' => (int)round($amount * 10),
            default => (int)round($amount),
        };
    }

    private function hasAnyTag(mixed $actualTags, array $expectedTags): bool
    {
        return is_array($actualTags)
            && count(array_intersect($actualTags, $expectedTags)) > 0;
    }
}
