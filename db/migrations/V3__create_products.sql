CREATE TABLE products (
                          id                 SERIAL        PRIMARY KEY,
                          name               VARCHAR(200)  NOT NULL,
                          slug               VARCHAR(220)  UNIQUE NOT NULL,
                          description        TEXT,
                          price              NUMERIC(10,2),
                          image_url          VARCHAR(500),
                          ingredients        TEXT,
                          barcode            VARCHAR(100),
                          brand              VARCHAR(100),
                          volume_ml          INT,
                          calories_per_100ml NUMERIC(7,2),
                          sugar_per_100ml    NUMERIC(7,2),
                          is_perishable      BOOLEAN       NOT NULL DEFAULT FALSE,
                          shelf_life_days    INT,
                          is_vegan           BOOLEAN       NOT NULL DEFAULT FALSE,
                          is_gluten_free     BOOLEAN       NOT NULL DEFAULT FALSE,
                          openfoodfacts_id   VARCHAR(100),
                          view_count         INT           NOT NULL DEFAULT 0,
                          created_by         INT           REFERENCES users(id) ON DELETE SET NULL,
                          created_at         TIMESTAMP     NOT NULL DEFAULT NOW(),
                          updated_at         TIMESTAMP     NOT NULL DEFAULT NOW()
);

-- many-to-many: produs <-> categorie
CREATE TABLE product_categories (
                                    product_id  INT NOT NULL REFERENCES products(id)   ON DELETE CASCADE,
                                    category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
                                    PRIMARY KEY (product_id, category_id)
);

CREATE TABLE product_allergens (
                                   product_id  INT NOT NULL REFERENCES products(id)  ON DELETE CASCADE,
                                   allergen_id INT NOT NULL REFERENCES allergens(id) ON DELETE CASCADE,
                                   PRIMARY KEY (product_id, allergen_id)
);

CREATE TABLE product_seasons (
                                 product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                                 season_id  INT NOT NULL REFERENCES seasons(id)  ON DELETE CASCADE,
                                 PRIMARY KEY (product_id, season_id)
);

CREATE TABLE product_regions (
                                 product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                                 region_id  INT NOT NULL REFERENCES regions(id)  ON DELETE CASCADE,
                                 PRIMARY KEY (product_id, region_id)
);

CREATE TABLE product_venues (
                                product_id     INT          NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                                venue_id       INT          NOT NULL REFERENCES venues(id)   ON DELETE CASCADE,
                                price_at_venue NUMERIC(10,2),
                                PRIMARY KEY (product_id, venue_id)
);

-- Indecsi
CREATE INDEX idx_products_slug       ON products(slug);
CREATE INDEX idx_products_brand      ON products(brand);
CREATE INDEX idx_products_view_count ON products(view_count DESC);
CREATE INDEX idx_pc_product          ON product_categories(product_id);