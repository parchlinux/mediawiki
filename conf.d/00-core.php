<?php
/**
 * 00-core.php: Site identity, paths, short URLs, language, and core media settings
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// Site Identification
$wgSitename = getenv( 'MW_SITENAME' ) ?: 'Parch Linux Wiki';
$wgMetaNamespace = getenv( 'MW_META_NAMESPACE' ) ?: 'Parch';

// URL Configuration (Short URLs: /wiki/$1)
$wgServer = getenv( 'MW_SERVER' ) ?: 'http://localhost';
$wgScriptPath = getenv( 'MW_SCRIPT_PATH' ) ?: '';
$wgArticlePath = '/wiki/$1';
$wgUsePathInfo = true;

// Debugging & Exception details
$wgShowExceptionDetails = true;
$wgShowDBErrorBacktrace = true;

// Email & Notification Configuration
$wgEmergencyContact = getenv( 'MW_EMERGENCY_CONTACT' ) ?: 'admin@parchlinux.com';
$wgPasswordSender = getenv( 'MW_PASSWORD_SENDER' ) ?: 'wiki@parchlinux.com';
$wgEnableEmail = true;
$wgEnableUserEmail = true;
$wgEnotifUserTalk = true;
$wgEnotifWatchlist = true;
$wgEmailAuthentication = true;

// Language & Localization
$wgLanguageCode = getenv( 'MW_LANG' ) ?: 'en';
$wgDefaultLanguageVariant = $wgLanguageCode;
$wgPageLanguageUseDB = true;

// Secrets & Encryption
$wgSecretKey = getenv( 'MW_SECRET_KEY' ) ?: 'parch_secret_key_default_development_only_1234567890abcdef';
$wgUpgradeKey = getenv( 'MW_UPGRADE_KEY' ) ?: 'parch_upgrade_key_development';

// File Uploads & Media
$wgEnableUploads = true;
$wgUploadDirectory = "$IP/images";
$wgUploadPath = "$wgScriptPath/images";
$wgGenerateThumbnailOnParse = true;
$wgMaxImageArea = 1.25e7;

// SVG & Modern Image Handling
$wgSVGConverter = 'rsvg';
$wgSVGConverters['rsvg'] = '$path/rsvg-convert -w $width -h $height $input -o $output';
$wgFileExtensions = array_unique( array_merge(
    isset( $wgFileExtensions ) && is_array( $wgFileExtensions ) ? $wgFileExtensions : [ 'png', 'gif', 'jpg', 'jpeg', 'webp' ],
    [ 'svg', 'ico', 'pdf', 'gz', 'tar', 'xz', 'zip', 'patch', 'diff' ]
) );

// Licensing
$wgRightsPage = '';
$wgRightsUrl = 'https://creativecommons.org/licenses/by-sa/4.0/';
$wgRightsText = 'Creative Commons Attribution-ShareAlike 4.0 International (CC BY-SA 4.0)';
$wgRightsIcon = "$wgScriptPath/resources/assets/licenses/cc-by-sa.png";
