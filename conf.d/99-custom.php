<?php
/**
 * 99-custom.php: Local environment overrides and development customizations
 * 
 * You can place custom configuration rules, debugging flags, or temporary overrides here.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// Enable debug mode if MW_DEBUG is set
if ( filter_var( getenv( 'MW_DEBUG' ) ?: false, FILTER_VALIDATE_BOOLEAN ) ) {
    $wgShowExceptionDetails = true;
    $wgShowDBErrorBacktrace = true;
    $wgDebugToolbar = true;
    $wgDevelopmentWarnings = true;
}
