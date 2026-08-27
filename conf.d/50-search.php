<?php
/**
 * 50-search.php: Lightweight native MariaDB full-text search & AdvancedSearch optimization
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// Enable AdvancedSearch extension for UI search filters
wfLoadExtensionIfExists( 'AdvancedSearch' );

// Optimize MariaDB full-text search settings
$wgEnableSearchContributorsByIP = false;
$wgSearchSuggestCacheExpiry = 86400; // 24 hours cache for search suggestions
