<?php
/**
 * 40-extensions.php: Curated Parch Linux Extension Stack Loader & Configuration
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. Core Editing, Highlighting & Templating
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'WikiEditor' );
wfLoadExtensionIfExists( 'VisualEditor' );
wfLoadExtensionIfExists( 'CodeMirror' );
wfLoadExtensionIfExists( 'SyntaxHighlight_GeSHi' );
wfLoadExtensionIfExists( 'Scribunto' );
wfLoadExtensionIfExists( 'TemplateStyles' );
wfLoadExtensionIfExists( 'TabberNeue' );

// VisualEditor Configuration
$wgVisualEditorAvailableNamespaces = [
    NS_MAIN => true,
    NS_USER => true,
    NS_HELP => true,
    NS_PROJECT => true,
];
$wgVisualEditorEnableWikitext = true;
$wgDefaultUserOptions['visualeditor-editor'] = 'visualeditor';

// CodeMirror Configuration
$wgCodeMirrorEnableBracketMatching = true;
$wgCodeMirrorLineNumbering = true;
$wgDefaultUserOptions['usecodemirror'] = 1;

// Scribunto Configuration (Lua Scripting)
$wgScribuntoDefaultEngine = 'luastandalone';
$wgScribuntoUseGeSHi = true;

// Pygments Syntax Highlighting
$wgPygmentizePath = '/usr/bin/pygmentize';

// -----------------------------------------------------------------------------
// 2. Structured Distro Data (Cargo & Page Forms)
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'Cargo' );
wfLoadExtensionIfExists( 'PageForms' );

// Cargo settings
$wgCargoPageDataColumns = [
    'full_text' => true,
    'num_inlinks' => true,
];

// -----------------------------------------------------------------------------
// 3. Search & Discovery
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'AdvancedSearch' );

// -----------------------------------------------------------------------------
// 4. Community & Collaboration
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'DiscussionTools' );
wfLoadExtensionIfExists( 'Echo' );

// DiscussionTools modern talk page defaults
$wgDiscussionToolsReplyTool = true;
$wgDiscussionToolsNewTopicTool = true;
$wgDiscussionToolsTopicSubscription = true;

// -----------------------------------------------------------------------------
// 5. Documentation, Localization & Citations
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'Cite' );
wfLoadExtensionIfExists( 'Translate' );
wfLoadExtensionIfExists( 'UniversalLanguageSelector' );

// Multi-language translation setup
$wgTranslateDocumentationLanguageCode = 'qqq';
$wgGroupPermissions['user']['translate'] = true;
$wgGroupPermissions['sysop']['translate-manage'] = true;

// -----------------------------------------------------------------------------
// 6. Media & Previews
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'MultimediaViewer' );
wfLoadExtensionIfExists( 'PageImages' );
wfLoadExtensionIfExists( 'TextExtracts' );

$wgMediaViewerIsInBeta = false;
$wgMediaViewerEnableByDefaultForAnonymous = true;
$wgMediaViewerEnableByDefault = true;

// -----------------------------------------------------------------------------
// 7. Anti-Spam & Moderation
// -----------------------------------------------------------------------------
wfLoadExtensionIfExists( 'AbuseFilter' );
wfLoadExtensionIfExists( 'ConfirmEdit' );
