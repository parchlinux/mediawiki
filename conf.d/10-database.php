<?php
/**
 * 10-database.php: Database credentials, connection pooling, and MariaDB optimization
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

$wgDBtype = getenv( 'MW_DB_TYPE' ) ?: 'mysql';
$wgDBserver = getenv( 'MW_DB_SERVER' ) ?: 'db';
$wgDBport = (int)( getenv( 'MW_DB_PORT' ) ?: 3306 );
$wgDBname = getenv( 'MW_DB_NAME' ) ?: 'parchwiki';
$wgDBuser = getenv( 'MW_DB_USER' ) ?: 'wikiuser';
$wgDBpassword = getenv( 'MW_DB_PASSWORD' ) ?: 'wikipass';

$wgDBprefix = getenv( 'MW_DB_PREFIX' ) ?: '';

// Table Engine and Collation optimization
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";
$wgSQLMode = "TRADITIONAL";

// Schema auto-maintenance
$wgSharedDB = null;
$wgSharedTables = [];
