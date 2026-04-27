CREATE TABLE categories (
                            id          SERIAL       PRIMARY KEY,
                            name        VARCHAR(100) UNIQUE NOT NULL,
                            slug        VARCHAR(100) UNIQUE NOT NULL,
                            description TEXT,
                            icon        VARCHAR(10),
                            color       VARCHAR(7)
);

CREATE TABLE allergens (
                           id          SERIAL       PRIMARY KEY,
                           name        VARCHAR(100) UNIQUE NOT NULL,
                           description TEXT,
                           icon        VARCHAR(10)
);

CREATE TABLE seasons (
                         id   SERIAL      PRIMARY KEY,
                         name VARCHAR(20) UNIQUE NOT NULL
                             CHECK (name IN ('spring', 'summer', 'autumn', 'winter'))
);

CREATE TABLE regions (
                         id      SERIAL       PRIMARY KEY,
                         name    VARCHAR(100) NOT NULL,
                         country VARCHAR(100) NOT NULL,
                         code    VARCHAR(10)
);

CREATE TABLE venues (
                        id         SERIAL       PRIMARY KEY,
                        name       VARCHAR(200) NOT NULL,
                        address    TEXT,
                        city       VARCHAR(100),
                        region_id  INT          REFERENCES regions(id) ON DELETE SET NULL,
                        website    VARCHAR(255),
                        created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);