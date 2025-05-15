<?php
// project/csrf.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// First time: generate a token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * @return string current CSRF token
 */
function csrf_token(): string
{
    return $_SESSION['csrf_token'];
}

/**
 * Call at the top of any POST-handler script.
 * Exits with 403 if token mismatch.
 */
function validate_csrf(): void
{
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}
