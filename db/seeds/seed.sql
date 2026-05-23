BEGIN;

-- Parola pentru ambele conturi: parola123
-- admin@sor.ro / parola123
-- user@sor.ro  / parola123

-- 1) Users
INSERT INTO users (username, email, password_hash, role, avatar_url)
VALUES
    ('admin_sor', 'admin@sor.ro', '$2y$12$zZ2A0bk8I5ZZH7mH5P0RK.E2ZLlKd4nqLPWU89fbQoV1fuR55vzte', 'admin', NULL),
    ('user_sor', 'user@sor.ro', '$2y$12$zZ2A0bk8I5ZZH7mH5P0RK.E2ZLlKd4nqLPWU89fbQoV1fuR55vzte', 'user', NULL)
    ON CONFLICT DO NOTHING;

-- 2) Categorii
INSERT INTO categories (name, slug, description, icon, color)
VALUES
    ('Ceaiuri', 'ceaiuri', 'Ceaiuri calde, reci, infuzii si bauturi pe baza de ceai.', '🍵', '#8df0c0'),
    ('Sucuri', 'sucuri', 'Sucuri naturale, carbogazoase si fresh-uri.', '🍊', '#f72585'),
    ('Lactate', 'lactate', 'Bauturi pe baza de lapte, iaurt sau cacao.', '🥛', '#ffffff'),
    ('Siropuri', 'siropuri', 'Siropuri pentru bauturi, limonade si cocktailuri fara alcool.', '🍓', '#ff7aa2'),
    ('Ape', 'ape', 'Apa plata, minerala si ape aromatizate.', '💧', '#7cc7ff'),
    ('Sezoniere', 'sezoniere', 'Produse disponibile mai ales in anumite sezoane.', '✨', '#ffd166')
    ON CONFLICT (slug) DO NOTHING;

-- 3) Alergeni
INSERT INTO allergens (name, description, icon)
VALUES
    ('gluten', 'Poate contine cereale cu gluten.', '🌾'),
    ('lactoza', 'Contine lapte sau produse derivate din lapte.', '🥛'),
    ('nuci', 'Poate contine nuci, alune sau migdale.', '🥜'),
    ('soia', 'Contine sau poate contine soia.', '🫘'),
    ('oua', 'Contine sau poate contine oua.', '🥚')
    ON CONFLICT (name) DO NOTHING;

-- 4) Sezoane
INSERT INTO seasons (name)
VALUES ('spring'), ('summer'), ('autumn'), ('winter')
    ON CONFLICT (name) DO NOTHING;

-- 5) Regiuni
INSERT INTO regions (name, country, code)
SELECT 'Moldova', 'Romania', 'RO-MD'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Moldova' AND country = 'Romania');

INSERT INTO regions (name, country, code)
SELECT 'Muntenia', 'Romania', 'RO-MT'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Muntenia' AND country = 'Romania');

INSERT INTO regions (name, country, code)
SELECT 'Transilvania', 'Romania', 'RO-TR'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Transilvania' AND country = 'Romania');

INSERT INTO regions (name, country, code)
SELECT 'Dobrogea', 'Romania', 'RO-DB'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Dobrogea' AND country = 'Romania');

INSERT INTO regions (name, country, code)
SELECT 'Basarabia', 'Moldova', 'MD-BS'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Basarabia' AND country = 'Moldova');

INSERT INTO regions (name, country, code)
SELECT 'Bavaria', 'Germany', 'DE-BY'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Bavaria' AND country = 'Germany');

INSERT INTO regions (name, country, code)
SELECT 'Toscana', 'Italy', 'IT-TO'
    WHERE NOT EXISTS (SELECT 1 FROM regions WHERE name = 'Toscana' AND country = 'Italy');

-- 6) Localuri / magazine
INSERT INTO venues (name, address, city, region_id, website)
SELECT 'Cafeneaua Verde', 'Strada Lapusneanu 12', 'Iasi', r.id, 'https://example.com/cafeneaua-verde'
FROM regions r
WHERE r.name = 'Moldova' AND r.country = 'Romania'
  AND NOT EXISTS (SELECT 1 FROM venues WHERE name = 'Cafeneaua Verde' AND city = 'Iasi');

INSERT INTO venues (name, address, city, region_id, website)
SELECT 'Tea Corner', 'Bulevardul Stefan cel Mare 21', 'Iasi', r.id, 'https://example.com/tea-corner'
FROM regions r
WHERE r.name = 'Moldova' AND r.country = 'Romania'
  AND NOT EXISTS (SELECT 1 FROM venues WHERE name = 'Tea Corner' AND city = 'Iasi');

INSERT INTO venues (name, address, city, region_id, website)
SELECT 'Fresh Bar Central', 'Piata Unirii 5', 'Bucuresti', r.id, 'https://example.com/fresh-bar-central'
FROM regions r
WHERE r.name = 'Muntenia' AND r.country = 'Romania'
  AND NOT EXISTS (SELECT 1 FROM venues WHERE name = 'Fresh Bar Central' AND city = 'Bucuresti');

INSERT INTO venues (name, address, city, region_id, website)
SELECT 'Market Bio Drinks', 'Strada Memorandumului 8', 'Cluj-Napoca', r.id, 'https://example.com/market-bio-drinks'
FROM regions r
WHERE r.name = 'Transilvania' AND r.country = 'Romania'
  AND NOT EXISTS (SELECT 1 FROM venues WHERE name = 'Market Bio Drinks' AND city = 'Cluj-Napoca');

-- 7) Produse
INSERT INTO products (
    name, slug, description, price, image_url, ingredients, barcode, brand,
    volume_ml, calories_per_100ml, sugar_per_100ml, is_perishable,
    shelf_life_days, is_vegan, is_gluten_free, openfoodfacts_id,
    view_count, created_by
)
VALUES
    ('Matcha Latte', 'matcha-latte', 'Bautura cremoasa cu matcha japonez si lapte.', 18.50, NULL, 'lapte, pudra matcha, zahar brun', '594000000001', 'SOr Drinks', 300, 92, 8.5, true, 3, false, true, NULL, 42, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Ceai Verde cu Iasomie', 'ceai-verde-iasomie', 'Ceai verde parfumat cu flori de iasomie.', 12.00, NULL, 'ceai verde, flori de iasomie', '594000000002', 'Leaf & Cup', 250, 2, 0, false, 365, true, true, NULL, 35, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Ceai Rece de Piersici', 'ceai-rece-piersici', 'Ice tea racoritor cu aroma de piersici.', 9.90, NULL, 'infuzie ceai negru, suc piersici, zahar, apa', '594000000003', 'CoolTea', 500, 36, 7.8, false, 180, true, true, NULL, 58, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Infuzie de Fructe de Padure', 'infuzie-fructe-padure', 'Infuzie aromata cu hibiscus si fructe de padure.', 11.50, NULL, 'hibiscus, macese, afine, zmeura, mure', '594000000004', 'Leaf & Cup', 250, 3, 0.2, false, 365, true, true, NULL, 22, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Fresh de Portocale', 'fresh-portocale', 'Suc proaspat stors din portocale.', 16.00, NULL, 'portocale', '594000000005', 'Fresh Bar', 330, 45, 8.9, true, 1, true, true, NULL, 73, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Limonada cu Menta', 'limonada-menta', 'Limonada fresh cu lamaie, menta si miere.', 14.50, NULL, 'apa, lamaie, menta, miere', '594000000006', 'Fresh Bar', 400, 32, 6.1, true, 2, false, true, NULL, 67, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Suc de Mere Presat la Rece', 'suc-mere-presat', 'Suc natural de mere, fara zahar adaugat.', 10.00, NULL, 'mere', '594000000007', 'Livada Buna', 330, 47, 10.1, false, 90, true, true, NULL, 28, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Suc Carbogazos de Soc', 'suc-carbogazos-soc', 'Bautura carbogazoasa cu aroma florilor de soc.', 8.50, NULL, 'apa carbogazoasa, extract flori de soc, zahar, lamaie', '594000000008', 'Socata Urban', 500, 39, 8.4, false, 240, true, true, NULL, 31, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Iaurt de Baut cu Capsuni', 'iaurt-baut-capsuni', 'Iaurt de baut cu piure de capsuni.', 7.90, NULL, 'lapte, culturi lactice, capsuni, zahar', '594000000009', 'LactoFresh', 330, 74, 9.5, true, 10, false, true, NULL, 49, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Lapte cu Cacao', 'lapte-cacao', 'Bautura cu lapte si cacao, potrivita pentru gustari.', 6.50, NULL, 'lapte, cacao, zahar', '594000000010', 'LactoFresh', 250, 83, 10.2, true, 7, false, true, NULL, 40, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Kefir Natural', 'kefir-natural', 'Bautura fermentata din lapte, cu gust usor acrisor.', 6.00, NULL, 'lapte, culturi lactice', '594000000011', 'Ferma Buna', 330, 52, 4.1, true, 12, false, true, NULL, 18, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Lapte de Ovaz Barista', 'lapte-ovaz-barista', 'Alternativa vegetala pentru cafea si bauturi calde.', 13.00, NULL, 'apa, ovaz, ulei floarea-soarelui, sare', '594000000012', 'PlantCup', 1000, 46, 3.2, false, 180, true, false, NULL, 55, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Sirop de Zmeura', 'sirop-zmeura', 'Sirop concentrat pentru limonade si ceaiuri reci.', 19.90, NULL, 'zmeura, zahar, apa, suc lamaie', '594000000013', 'SweetDrop', 500, 240, 58, false, 365, true, true, NULL, 33, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Sirop de Soc', 'sirop-soc', 'Sirop aromat din flori de soc.', 18.90, NULL, 'flori de soc, zahar, apa, lamaie', '594000000014', 'SweetDrop', 500, 230, 55, false, 365, true, true, NULL, 26, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Sirop de Ghimbir', 'sirop-ghimbir', 'Sirop intens de ghimbir pentru bauturi racoritoare.', 21.00, NULL, 'ghimbir, zahar, apa, lamaie', '594000000015', 'GingerHouse', 500, 245, 57, false, 365, true, true, NULL, 21, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Apa Minerala Carbogazoasa', 'apa-minerala-carbogazoasa', 'Apa minerala carbogazoasa imbuteliata.', 4.50, NULL, 'apa minerala naturala, dioxid de carbon', '594000000016', 'AquaMold', 500, 0, 0, false, 730, true, true, NULL, 44, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Apa Plata', 'apa-plata', 'Apa plata de izvor.', 4.00, NULL, 'apa plata', '594000000017', 'AquaMold', 500, 0, 0, false, 730, true, true, NULL, 39, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Apa Aromatizata cu Lamaie', 'apa-aromatizata-lamaie', 'Apa cu aroma naturala de lamaie, fara zahar.', 6.50, NULL, 'apa, aroma naturala lamaie', '594000000018', 'AquaMold', 500, 1, 0, false, 365, true, true, NULL, 52, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Pumpkin Spice Latte', 'pumpkin-spice-latte', 'Bautura sezoniera de toamna cu lapte, dovleac si condimente.', 20.00, NULL, 'lapte, piure dovleac, scortisoara, nucsoara, zahar', '594000000019', 'SOr Drinks', 350, 110, 11.8, true, 3, false, true, NULL, 64, (SELECT id FROM users WHERE email = 'admin@sor.ro')),
    ('Ciocolata Calda cu Alune', 'ciocolata-calda-alune', 'Bautura calda cu cacao si aroma de alune.', 17.50, NULL, 'lapte, cacao, zahar, pasta de alune', '594000000020', 'WinterCup', 300, 125, 13.5, true, 3, false, true, NULL, 61, (SELECT id FROM users WHERE email = 'admin@sor.ro'))
    ON CONFLICT (slug) DO NOTHING;

-- 8) Legaturi produs-categorie
INSERT INTO product_categories (product_id, category_id)
SELECT p.id, c.id
FROM products p
         JOIN categories c ON c.slug IN (
                                         CASE WHEN p.slug IN ('matcha-latte', 'ceai-verde-iasomie', 'ceai-rece-piersici', 'infuzie-fructe-padure') THEN 'ceaiuri' END,
                                         CASE WHEN p.slug IN ('fresh-portocale', 'limonada-menta', 'suc-mere-presat', 'suc-carbogazos-soc') THEN 'sucuri' END,
                                         CASE WHEN p.slug IN ('matcha-latte', 'iaurt-baut-capsuni', 'lapte-cacao', 'kefir-natural', 'lapte-ovaz-barista', 'pumpkin-spice-latte', 'ciocolata-calda-alune') THEN 'lactate' END,
                                         CASE WHEN p.slug IN ('sirop-zmeura', 'sirop-soc', 'sirop-ghimbir') THEN 'siropuri' END,
                                         CASE WHEN p.slug IN ('apa-minerala-carbogazoasa', 'apa-plata', 'apa-aromatizata-lamaie') THEN 'ape' END,
                                         CASE WHEN p.slug IN ('ceai-rece-piersici', 'limonada-menta', 'sirop-zmeura', 'sirop-soc', 'pumpkin-spice-latte', 'ciocolata-calda-alune') THEN 'sezoniere' END
    )
    ON CONFLICT DO NOTHING;

-- 9) Legaturi produs-alergeni
INSERT INTO product_allergens (product_id, allergen_id)
SELECT p.id, a.id
FROM products p
         JOIN allergens a ON a.name IN (
                                        CASE WHEN p.slug IN ('matcha-latte', 'iaurt-baut-capsuni', 'lapte-cacao', 'kefir-natural', 'pumpkin-spice-latte', 'ciocolata-calda-alune') THEN 'lactoza' END,
                                        CASE WHEN p.slug IN ('ciocolata-calda-alune') THEN 'nuci' END,
                                        CASE WHEN p.slug IN ('lapte-ovaz-barista') THEN 'gluten' END
    )
    ON CONFLICT DO NOTHING;

-- 10) Legaturi produs-sezoane
INSERT INTO product_seasons (product_id, season_id)
SELECT p.id, s.id
FROM products p
         JOIN seasons s ON s.name IN (
                                      CASE WHEN p.slug IN ('ceai-verde-iasomie', 'infuzie-fructe-padure', 'suc-mere-presat', 'apa-plata') THEN 'spring' END,
                                      CASE WHEN p.slug IN ('ceai-rece-piersici', 'fresh-portocale', 'limonada-menta', 'suc-carbogazos-soc', 'sirop-zmeura', 'sirop-soc', 'apa-minerala-carbogazoasa', 'apa-aromatizata-lamaie') THEN 'summer' END,
                                      CASE WHEN p.slug IN ('matcha-latte', 'suc-mere-presat', 'sirop-ghimbir', 'pumpkin-spice-latte') THEN 'autumn' END,
                                      CASE WHEN p.slug IN ('matcha-latte', 'lapte-cacao', 'kefir-natural', 'lapte-ovaz-barista', 'ciocolata-calda-alune') THEN 'winter' END
    )
    ON CONFLICT DO NOTHING;

-- 11) Legaturi produs-regiuni
INSERT INTO product_regions (product_id, region_id)
SELECT p.id, r.id
FROM products p
         JOIN regions r ON r.name IN (
                                      CASE WHEN p.slug IN ('matcha-latte', 'ceai-verde-iasomie', 'ceai-rece-piersici', 'infuzie-fructe-padure', 'fresh-portocale', 'limonada-menta', 'apa-plata') THEN 'Moldova' END,
                                      CASE WHEN p.slug IN ('fresh-portocale', 'suc-carbogazos-soc', 'sirop-zmeura', 'apa-minerala-carbogazoasa', 'apa-aromatizata-lamaie') THEN 'Muntenia' END,
                                      CASE WHEN p.slug IN ('suc-mere-presat', 'iaurt-baut-capsuni', 'lapte-cacao', 'kefir-natural', 'lapte-ovaz-barista') THEN 'Transilvania' END,
                                      CASE WHEN p.slug IN ('sirop-soc', 'sirop-ghimbir', 'apa-minerala-carbogazoasa') THEN 'Dobrogea' END,
                                      CASE WHEN p.slug IN ('pumpkin-spice-latte', 'ciocolata-calda-alune') THEN 'Bavaria' END,
                                      CASE WHEN p.slug IN ('limonada-menta', 'apa-aromatizata-lamaie') THEN 'Toscana' END
    )
    ON CONFLICT DO NOTHING;

-- 12) Legaturi produs-localuri
INSERT INTO product_venues (product_id, venue_id, price_at_venue)
SELECT p.id, v.id,
       CASE
           WHEN v.name = 'Cafeneaua Verde' THEN p.price + 2
           WHEN v.name = 'Tea Corner' THEN p.price + 1.5
           WHEN v.name = 'Fresh Bar Central' THEN p.price + 2.5
           ELSE p.price + 1
           END
FROM products p
         JOIN venues v ON (
    (v.name = 'Cafeneaua Verde' AND p.slug IN ('matcha-latte', 'ceai-verde-iasomie', 'infuzie-fructe-padure', 'pumpkin-spice-latte', 'ciocolata-calda-alune'))
        OR (v.name = 'Tea Corner' AND p.slug IN ('ceai-rece-piersici', 'ceai-verde-iasomie', 'matcha-latte', 'sirop-soc'))
        OR (v.name = 'Fresh Bar Central' AND p.slug IN ('fresh-portocale', 'limonada-menta', 'suc-mere-presat', 'suc-carbogazos-soc', 'apa-aromatizata-lamaie'))
        OR (v.name = 'Market Bio Drinks' AND p.slug IN ('lapte-ovaz-barista', 'apa-plata', 'apa-minerala-carbogazoasa', 'sirop-zmeura', 'sirop-ghimbir'))
    )
    ON CONFLICT DO NOTHING;

-- 13) Optional: cateva favorite si ratinguri, ca statisticile sa nu fie goale
INSERT INTO user_favorites (user_id, product_id)
SELECT u.id, p.id
FROM users u
         JOIN products p ON p.slug IN ('matcha-latte', 'ceai-rece-piersici', 'fresh-portocale', 'limonada-menta', 'apa-aromatizata-lamaie')
WHERE u.email = 'user@sor.ro'
    ON CONFLICT DO NOTHING;

INSERT INTO product_ratings (user_id, product_id, rating, review)
SELECT u.id, p.id, 5, 'Foarte bun si usor de gasit in catalog.'
FROM users u
         JOIN products p ON p.slug IN ('matcha-latte', 'fresh-portocale')
WHERE u.email = 'user@sor.ro'
    ON CONFLICT (user_id, product_id) DO NOTHING;

INSERT INTO product_ratings (user_id, product_id, rating, review)
SELECT u.id, p.id, 4, 'Gust bun, l-as mai cumpara.'
FROM users u
         JOIN products p ON p.slug IN ('ceai-rece-piersici', 'limonada-menta', 'apa-aromatizata-lamaie')
WHERE u.email = 'user@sor.ro'
    ON CONFLICT (user_id, product_id) DO NOTHING;

COMMIT;
