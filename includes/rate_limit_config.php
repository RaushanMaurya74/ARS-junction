<?php
/**
 * ARS Junction - Rate Limiting Configuration
 * All thresholds are configurable via environment variables (.env / server env) with safe fallback defaults.
 */

if (!defined('RL_CONFIG_LOADED')) {
    define('RL_CONFIG_LOADED', true);

    // Authentication Routes (strictest limits, per-IP + per-account, exponential backoff)
    if (!defined('RL_AUTH_IP_MAX')) {
        define('RL_AUTH_IP_MAX', (int)(getenv('RL_AUTH_IP_MAX') ?: 5));
    }
    if (!defined('RL_AUTH_ACCOUNT_MAX')) {
        define('RL_AUTH_ACCOUNT_MAX', (int)(getenv('RL_AUTH_ACCOUNT_MAX') ?: 5));
    }
    if (!defined('RL_AUTH_WINDOW')) {
        define('RL_AUTH_WINDOW', (int)(getenv('RL_AUTH_WINDOW') ?: 900)); // 15 minutes window
    }
    if (!defined('RL_AUTH_BACKOFF_BASE')) {
        define('RL_AUTH_BACKOFF_BASE', (int)(getenv('RL_AUTH_BACKOFF_BASE') ?: 5)); // Base delay 5s
    }
    if (!defined('RL_AUTH_BACKOFF_MAX')) {
        define('RL_AUTH_BACKOFF_MAX', (int)(getenv('RL_AUTH_BACKOFF_MAX') ?: 1800)); // Max delay 30 mins
    }

    // Public Endpoints (moderate limits, per-IP sliding window)
    if (!defined('RL_PUBLIC_MAX')) {
        define('RL_PUBLIC_MAX', (int)(getenv('RL_PUBLIC_MAX') ?: 60)); // 60 requests
    }
    if (!defined('RL_PUBLIC_WINDOW')) {
        define('RL_PUBLIC_WINDOW', (int)(getenv('RL_PUBLIC_WINDOW') ?: 60)); // per 60 seconds
    }

    // Authenticated User Actions (looser limits, per user ID / IP sliding window)
    if (!defined('RL_AUTHED_MAX')) {
        define('RL_AUTHED_MAX', (int)(getenv('RL_AUTHED_MAX') ?: 200)); // 200 requests
    }
    if (!defined('RL_AUTHED_WINDOW')) {
        define('RL_AUTHED_WINDOW', (int)(getenv('RL_AUTHED_WINDOW') ?: 60)); // per 60 seconds
    }
}
