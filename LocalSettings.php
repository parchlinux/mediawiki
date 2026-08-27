<?php
/**
 * Parch Linux MediaWiki - Main Configuration Loader
 * 
 * Modular 12-factor configuration architecture.
 * Loads individual configuration modules in alphabetical order from conf.d/
 * 
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// Global helper: load extension safely if installed
if ( !function_exists( 'wfLoadExtensionIfExists' ) ) {
    function wfLoadExtensionIfExists( string $extensionName ): bool {
        global $IP;
        $extJson = "$IP/extensions/$extensionName/extension.json";
        if ( file_exists( $extJson ) ) {
            wfLoadExtension( $extensionName );
            return true;
        }
        return false;
    }
}

// Global helper: load skin safely if installed
if ( !function_exists( 'wfLoadSkinIfExists' ) ) {
    function wfLoadSkinIfExists( string $skinName ): bool {
        global $IP;
        $skinJson = "$IP/skins/$skinName/skin.json";
        if ( file_exists( $skinJson ) ) {
            wfLoadSkin( $skinName );
            return true;
        }
        return false;
    }
}

// Load modular configuration files in sequential order
$confDir = __DIR__ . '/conf.d';
if ( is_dir( $confDir ) ) {
    $configFiles = glob( $confDir . '/*.php' );
    if ( $configFiles !== false ) {
        sort( $configFiles, SORT_NATURAL );
        foreach ( $configFiles as $configFile ) {
            require $configFile;
        }
    }
}
