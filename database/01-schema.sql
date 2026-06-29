CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20)  NOT NULL CHECK (role IN ('admin', 'attendant')),
    created_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Email unico apenas entre os registros ativos. Com soft delete, um UNIQUE comum
-- impediria recadastrar um e-mail que foi "excluído" (o registro fantasma o ocuparia).
CREATE UNIQUE INDEX idx_users_email_active ON users (email) WHERE deleted_at IS NULL;

CREATE TABLE availability (
    id          SERIAL    PRIMARY KEY,
    user_id     INTEGER   NOT NULL REFERENCES users (id),
    day_of_week SMALLINT  NOT NULL CHECK (day_of_week BETWEEN 0 AND 6), -- 0=domingo ... 6=sabado
    start_time  TIME      NOT NULL,
    end_time    TIME      NOT NULL,
    active      BOOLEAN   NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMP,
    CHECK (end_time > start_time)
);

CREATE TABLE appointments (
    id           SERIAL       PRIMARY KEY,
    attendant_id INTEGER      NOT NULL REFERENCES users (id),
    date         DATE         NOT NULL,
    start_time   TIME         NOT NULL,
    end_time     TIME         NOT NULL,
    client_name  VARCHAR(100) NOT NULL,
    client_email VARCHAR(100),
    status       VARCHAR(20)  NOT NULL DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'cancelled')),
    created_at   TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMP    NOT NULL DEFAULT NOW(),
    deleted_at   TIMESTAMP,
    CHECK (end_time > start_time)
);

-- A consulta de horários disponíveis filtra por atendente + data.
CREATE INDEX idx_appointments_attendant_date ON appointments (attendant_id, date);
