CREATE TABLE shopping_lists (
                                id          SERIAL       PRIMARY KEY,
                                user_id     INT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                name        VARCHAR(200) NOT NULL,
                                is_shared   BOOLEAN      NOT NULL DEFAULT FALSE,
                                share_token VARCHAR(64)  UNIQUE,
                                created_at  TIMESTAMP    NOT NULL DEFAULT NOW(),
                                updated_at  TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE shopping_list_items (
                                     id           SERIAL    PRIMARY KEY,
                                     list_id      INT       NOT NULL REFERENCES shopping_lists(id) ON DELETE CASCADE,
                                     product_id   INT       NOT NULL REFERENCES products(id)       ON DELETE CASCADE,
                                     quantity     INT       NOT NULL DEFAULT 1,
                                     notes        TEXT,
                                     is_purchased BOOLEAN   NOT NULL DEFAULT FALSE,
                                     added_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_sl_user         ON shopping_lists(user_id);
CREATE INDEX idx_sl_share_token  ON shopping_lists(share_token);
CREATE INDEX idx_sl_items_list   ON shopping_list_items(list_id);