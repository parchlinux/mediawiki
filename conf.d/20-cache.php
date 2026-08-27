<?php
/**
 * 20-cache.php: Redis object cache, APCu acceleration, and session storage
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

$redisServer = getenv( 'MW_REDIS_SERVER' );
$redisPassword = getenv( 'MW_REDIS_PASSWORD' ) ?: null;

if ( $redisServer ) {
    $redisConfig = [
        'class' => 'RedisBagOStuff',
        'servers' => [ $redisServer ],
        'loggroup' => 'redis',
    ];
    if ( $redisPassword ) {
        $redisConfig['password'] = $redisPassword;
    }

    $wgObjectCaches['redis'] = $redisConfig;
    $wgMainCacheType = 'redis';
    $wgMessageCacheType = 'redis';
    $wgParserCacheType = 'redis';
    $wgSessionCacheType = 'redis';
} else {
    // Fallback to APCu or local caching
    if ( extension_loaded( 'apcu' ) && ini_get( 'apc.enabled' ) ) {
        $wgMainCacheType = CACHE_ACCEL;
        $wgMessageCacheType = CACHE_ACCEL;
        $wgParserCacheType = CACHE_DB;
    } else {
        $wgMainCacheType = CACHE_DB;
    }
}

// Fast Localisation Cache
$wgLocalisationCacheConf['type'] = 'array';
