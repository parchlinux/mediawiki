<?php
/**
 * 60-security.php: Authentication, permissions, anti-spam CAPTCHA, and security headers
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// Cloudflare Turnstile / CAPTCHA Integration
// -----------------------------------------------------------------------------
$turnstileSiteKey = getenv( 'MW_TURNSTILE_SITE_KEY' );
$turnstileSecretKey = getenv( 'MW_TURNSTILE_SECRET_KEY' );

if ( $turnstileSiteKey && $turnstileSecretKey ) {
    wfLoadExtensionIfExists( 'ConfirmEdit/Turnstile' );
    $wgTurnstileSiteKey = $turnstileSiteKey;
    $wgTurnstileSecretKey = $turnstileSecretKey;

    $wgCaptchaClass = 'MediaWiki\\Extension\\ConfirmEdit\\Turnstile\\Turnstile';
    $wgCaptchaTriggers['edit'] = true;
    $wgCaptchaTriggers['create'] = true;
    $wgCaptchaTriggers['createtalk'] = true;
    $wgCaptchaTriggers['addurl'] = true;
    $wgCaptchaTriggers['createaccount'] = true;
    $wgCaptchaTriggers['badlogin'] = true;
}

// -----------------------------------------------------------------------------
// Group Permissions & Access Control
// -----------------------------------------------------------------------------
// Allow anonymous reading by default
$wgGroupPermissions['*']['read'] = true;

// Prevent anonymous editing to avoid vandalism (require registered user)
$requireLoginToEdit = getenv( 'MW_REQUIRE_LOGIN_TO_EDIT' ) ?: 'true';
if ( filter_var( $requireLoginToEdit, FILTER_VALIDATE_BOOLEAN ) ) {
    $wgGroupPermissions['*']['edit'] = false;
    $wgGroupPermissions['*']['createpage'] = false;
    $wgGroupPermissions['*']['createtalk'] = false;
    $wgGroupPermissions['*']['writeapi'] = false;

    $wgGroupPermissions['user']['edit'] = true;
    $wgGroupPermissions['user']['createpage'] = true;
    $wgGroupPermissions['user']['createtalk'] = true;
    $wgGroupPermissions['user']['writeapi'] = true;
    $wgGroupPermissions['user']['minoredit'] = true;
    $wgGroupPermissions['user']['upload'] = true;
}

// -----------------------------------------------------------------------------
// Session & Cookie Hardening
// -----------------------------------------------------------------------------
$wgCookieSecure = 'detect';
$wgCookieHttpOnly = true;
$wgCookieSameSite = 'Lax';

// Password Security Policy
$wgPasswordPolicy['policies']['default']['MinimalPasswordLength'] = 8;
$wgPasswordPolicy['policies']['sysop']['MinimalPasswordLength'] = 12;
