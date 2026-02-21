<?php

/**
 * Candidate authentication helpers.
 * 
 * Google Identity Services flow:
 * 1. Client receives an id_token from Google Sign-In button
 * 2. Client POSTs the token to the server
 * 3. Server verifies via Google tokeninfo API
 * 4. Server stores decoded profile in $_SESSION['candidate']
 * 
 * REPLACE_WITH_YOUR_CLIENT_ID in assembler/register page with your actual Google Client ID
 */

define('CANDIDATE_GOOGLE_CLIENT_ID', 'REPLACE_WITH_YOUR_CLIENT_ID');
define('CANDIDATE_SESSION_KEY', 'candidate_profile');

/**
 * Verifies a Google ID token server-side.
 * Returns the decoded payload on success, false on failure.
 * 
 * After getting a valid payload, the caller MUST check that:
 *   - payload['email_verified'] === true
 *   - ends_with(payload['email'], '@go.uop.gr')
 */
function candidate_verify_google_token(string $id_token): array|false
{
    // Use Google's tokeninfo endpoint for server-side verification
    // (does not require any Google library)
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
        ]
    ]);

    $response = @file_get_contents($url, false, $ctx);

    if ($response === false) {
        return false;
    }

    $payload = json_decode($response, true);

    if (!is_array($payload)) {
        return false;
    }

    // Must be issued for our client
    // We only enforce this when a real client ID is set
    if (CANDIDATE_GOOGLE_CLIENT_ID !== 'REPLACE_WITH_YOUR_CLIENT_ID') {
        if (($payload['aud'] ?? '') !== CANDIDATE_GOOGLE_CLIENT_ID) {
            return false;
        }
    }

    // Token must not be expired (Google already checks this but we double-check)
    if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Returns true if the email is a valid @go.uop.gr student account.
 */
function candidate_is_uop_email(string $email): bool
{
    return str_ends_with(strtolower(trim($email)), '@go.uop.gr');
}

/**
 * Starts the candidate session (call before any session access).
 */
function candidate_session_ensure_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Returns the current candidate profile from session, or false if not logged in.
 * 
 * Returns array with keys: google_sub, email, display_name, avatar_url
 */
function candidate_session_get(): array|false
{
    candidate_session_ensure_started();

    $profile = $_SESSION[CANDIDATE_SESSION_KEY] ?? null;

    if (!is_array($profile) || empty($profile['google_sub']) || empty($profile['email'])) {
        return false;
    }

    return $profile;
}

/**
 * Stores the candidate profile in the session.
 */
function candidate_session_set(array $profile): void
{
    candidate_session_ensure_started();
    $_SESSION[CANDIDATE_SESSION_KEY] = $profile;
}

/**
 * Clears the candidate session.
 */
function candidate_session_clear(): void
{
    candidate_session_ensure_started();
    unset($_SESSION[CANDIDATE_SESSION_KEY]);
}

/**
 * Require candidate to be logged in. Redirects to register page if not.
 * Returns the candidate profile.
 */
function candidate_require_auth(): array
{
    $profile = candidate_session_get();

    if ($profile === false) {
        header('Location: /candidate_register.php');
        exit;
    }

    return $profile;
}
