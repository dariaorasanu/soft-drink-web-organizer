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
        if (!empty($data['products']) && is_array($data['products'])) {
            return $this->sortBySearchRelevance($data['products'], $name);
        }

        // Endpointul clasic de search poate raspunde intermitent cu 503.
        // Folosim indexul de cautare Open Food Facts ca fallback.
        return $this->searchByNameFallback($name);
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

            'description' => $this->descriptionText($offData),

            'price' => null,

            'image_url' => $this->firstValue($offData, [
                'image_front_url',
                'image_url',
            ]),

            'ingredients' => $this->ingredientsText($offData),

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

    private function ingredientsText(array $data): ?string
    {
        // Preferam ingrediente localizate. Daca exista doar text generic in franceza,
        // il lasam gol ca adminul sa il completeze/verifice manual.
        $localized = $this->firstValue($data, [
            'ingredients_text_ro',
            'ingredients_text_en',
        ]);

        if ($localized !== null) {
            return $localized;
        }

        $generic = $this->firstValue($data, ['ingredients_text']);
        if ($generic === null || $this->looksLikeFrench($generic)) {
            return null;
        }

        return $generic;
    }

    private function descriptionText(array $data): ?string
    {
        // Descrierile din OFF pot fi in multe limbi. Pastram romana/engleza
        // si evitam completarea automata cu texte in limbi greu de validat.
        $localized = $this->firstValue($data, [
            'generic_name_ro',
            'generic_name_en',
        ]);

        if ($localized !== null) {
            return $this->cleanImportedText($localized);
        }

        $generic = $this->firstValue($data, ['generic_name']);
        if ($generic !== null && !$this->looksLikeUnsupportedLanguage($generic)) {
            return $this->cleanImportedText($generic);
        }

        $categories = $this->firstValue($data, ['categories']);
        if ($categories !== null && !$this->looksLikeUnsupportedLanguage($categories)) {
            return $this->cleanImportedText($categories);
        }

        return null;
    }

    private function looksLikeFrench(string $text): bool
    {
        $text = strtolower($text);

        return preg_match(
            "/\\b(eau|sucre|acidifiants?|stabilisant|antioxydant|concentre|concentre|concentré|purée|arômes?|matières?|abricot|fraise)\\b|\\bd'|jus d'/u",
            $text
        ) === 1;
    }

    private function looksLikeUnsupportedLanguage(string $text): bool
    {
        $text = strtolower($text);

        return $this->looksLikeFrench($text)
            || preg_match('/[\\x{0600}-\\x{06FF}\\x{0400}-\\x{04FF}\\x{0590}-\\x{05FF}\\x{4E00}-\\x{9FFF}]/u', $text) === 1
            || preg_match('/\\b(boisson|boissons|bebida|bebidas|zumo|jus|wasser|getrank|getränk|getränke|pflanzliche|lebensmittel|napoj|succo)\\b/u', $text) === 1;
    }

    private function cleanImportedText(string $text): string
    {
        $text = preg_replace('/\\b[a-z]{2}:/i', '', $text);
        $text = str_replace('-', ' ', $text);
        $text = preg_replace('/\\s+/', ' ', $text);

        return trim($text ?? '');
    }

    private function searchByNameFallback(string $name): array
    {
        $url = 'https://search.openfoodfacts.org/search?' . http_build_query([
            'q' => $name,
        ]);

        $data = $this->getJson($url);
        if (empty($data['hits']) || !is_array($data['hits'])) {
            return [];
        }

        return array_slice($this->sortBySearchRelevance($data['hits'], $name), 0, 10);
    }

    private function sortBySearchRelevance(array $products, string $query): array
    {
        usort($products, function (array $a, array $b) use ($query) {
            return $this->relevanceScore($b, $query) <=> $this->relevanceScore($a, $query);
        });

        return array_values($products);
    }

    private function relevanceScore(array $product, string $query): int
    {
        // OFF intoarce uneori produse din acelasi brand, dar fara termenul cautat in nume.
        // Scorul pune mai sus potrivirile din nume si produsele cu date mai complete.
        $query = $this->normalizeSearchText($query);

        $name = $this->normalizeSearchText((string)$this->firstValue($product, [
            'product_name_ro',
            'product_name_en',
            'product_name',
        ]));
        $genericName = $this->normalizeSearchText((string)$this->firstValue($product, [
            'generic_name_ro',
            'generic_name_en',
            'generic_name',
        ]));
        $brand = $this->normalizeSearchText((string)$this->firstBrand($product['brands'] ?? null));
        $categories = $this->normalizeSearchText(
            is_array($product['categories_tags'] ?? null)
                ? implode(' ', $product['categories_tags'])
                : (string)($product['categories'] ?? '')
        );

        $score = 0;

        if ($name === $query) $score += 220;
        if ($name !== '' && str_starts_with($name, $query)) $score += 160;
        if ($name !== '' && str_contains($name, $query)) $score += 120;
        if ($genericName !== '' && str_contains($genericName, $query)) $score += 60;
        if ($brand === $query) $score += 45;
        if ($brand !== '' && str_contains($brand, $query)) $score += 30;
        if (str_contains($categories, 'cola') || str_contains($categories, 'soda')) $score += 15;
        if (!empty($product['image_front_url']) || !empty($product['image_url'])) $score += 40;
        if (!empty($product['quantity'])) $score += 15;
        if (!empty($product['nutriments']) && is_array($product['nutriments'])) $score += 10;

        return $score;
    }

    private function normalizeSearchText(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }

    private function firstBrand(mixed $brands): ?string
    {
        if (is_array($brands)) {
            $brands = implode(',', $brands);
        }

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
