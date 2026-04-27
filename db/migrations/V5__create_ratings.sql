CREATE TABLE user_favorites (
                                user_id    INT NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
                                product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                                PRIMARY KEY (user_id, product_id)
);

CREATE TABLE product_ratings (
                                 id         SERIAL   PRIMARY KEY,
                                 user_id    INT      NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
                                 product_id INT      NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                                 rating     SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                                 review     TEXT,
                                 created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                                 UNIQUE (user_id, product_id)
);

CREATE TABLE product_views (
                               id         SERIAL    PRIMARY KEY,
                               product_id INT       NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                               user_id    INT       REFERENCES users(id) ON DELETE SET NULL,
                               viewed_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_pv_product  ON product_views(product_id);
CREATE INDEX idx_pv_viewed   ON product_views(viewed_at DESC);
CREATE INDEX idx_pr_product  ON product_ratings(product_id);