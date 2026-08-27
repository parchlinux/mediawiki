<?php
/**
 * 30-skin.php: Citizen skin activation, Parch Linux branding, theme tokens, and typography
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

// Attempt to load Citizen skin (primary modern theme)
if ( wfLoadSkinIfExists( 'Citizen' ) ) {
    $wgDefaultSkin = 'citizen';

    // Citizen Skin Customization & Parch Branding Tokens
    $wgCitizenThemeColor = '#16a085'; // Parch Turquoise
    $wgCitizenEnableCollapsibleSections = true;
    $wgCitizenSearchGateway = 'mwActionApi';

    // Parch theme design tokens injected into page head
    $wgHooks['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
        if ( $skin->getSkinName() === 'citizen' ) {
            $customCss = <<<CSS
:root {
    --color-primary: #16a085;
    --color-primary--hover: #1abc9c;
    --color-primary--active: #149174;
    --color-primary__subtle: rgba(22, 160, 133, 0.15);
    --font-family-monospace: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, Monaco, Consolas, monospace;
}

[data-theme="dark"] {
    --color-surface-0: #14171d;
    --color-surface-1: #1b2028;
    --color-surface-2: #242b35;
    --color-surface-3: #2e3744;
    --color-primary: #1abc9c;
    --color-primary--hover: #48c9b0;
    --color-primary--active: #16a085;
}

/* Enhanced Code Blocks & Syntax Styling */
.mw-highlight pre, pre, code {
    font-family: var(--font-family-monospace) !important;
    font-size: 0.9em;
    border-radius: 6px;
}

/* Parch Callouts / Alerts */
.parch-callout {
    border-left: 4px solid var(--color-primary);
    background-color: var(--color-primary__subtle);
    padding: 12px 16px;
    margin: 16px 0;
    border-radius: 0 8px 8px 0;
}
.parch-callout-tip { border-left-color: #2ecc71; background-color: rgba(46, 204, 113, 0.12); }
.parch-callout-warning { border-left-color: #e67e22; background-color: rgba(230, 126, 34, 0.12); }
.parch-callout-danger { border-left-color: #e74c3c; background-color: rgba(231, 76, 60, 0.12); }
.parch-callout-info { border-left-color: #3498db; background-color: rgba(52, 152, 219, 0.12); }
CSS;
            $out->addInlineStyle( $customCss );
        }
    };
} else {
    // Fallback skins if Citizen is not yet fetched
    if ( wfLoadSkinIfExists( 'Vector' ) ) {
        $wgDefaultSkin = 'vector-2022';
    } elseif ( wfLoadSkinIfExists( 'MonoBook' ) ) {
        $wgDefaultSkin = 'monobook';
    }
}
