-- Rate Limiting Tables Schema for ARS Junction
-- Compatible with MySQL and PostgreSQL (Supabase)

CREATE TABLE IF NOT EXISTS rate_limits (
    rate_key VARCHAR(191) PRIMARY KEY,
    hits INT NOT NULL DEFAULT 1,
    reset_at BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS auth_attempts (
    attempt_key VARCHAR(191) PRIMARY KEY,
    attempt_count INT NOT NULL DEFAULT 0,
    last_attempt BIGINT NOT NULL DEFAULT 0,
    lockout_until BIGINT NOT NULL DEFAULT 0
);
