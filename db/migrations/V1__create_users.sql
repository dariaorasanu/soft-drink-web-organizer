CREATE TABLE users (
                       id            SERIAL        PRIMARY KEY,
                       username      VARCHAR(50)   UNIQUE NOT NULL,
                       email         VARCHAR(150)  UNIQUE NOT NULL,
                       password_hash VARCHAR(255)  NOT NULL,
                       role          VARCHAR(10)   NOT NULL DEFAULT 'user'
                           CHECK (role IN ('admin', 'user')),
                       avatar_url    VARCHAR(500),
                       created_at    TIMESTAMP     NOT NULL DEFAULT NOW(),
                       updated_at    TIMESTAMP     NOT NULL DEFAULT NOW()
);