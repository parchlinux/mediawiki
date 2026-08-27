#!/usr/bin/env bash
set -euo pipefail

# install-extensions.sh: Downloads and installs curated extensions and skins for Parch Linux Wiki

MW_INSTALL_PATH="${MW_INSTALL_PATH:-/var/www/html}"
MW_BRANCH="${MW_BRANCH:-master}"

EXT_DIR="${MW_INSTALL_PATH}/extensions"
SKIN_DIR="${MW_INSTALL_PATH}/skins"

mkdir -p "${EXT_DIR}" "${SKIN_DIR}"

echo "==> Installing Citizen Skin for Parch Linux..."
if [ ! -d "${SKIN_DIR}/Citizen" ]; then
    git clone --depth 1 https://github.com/StarCitizenTools/mediawiki-skins-Citizen.git "${SKIN_DIR}/Citizen"
fi

# List of official Wikimedia extensions
WIKIMEDIA_EXTENSIONS=(
    "VisualEditor"
    "WikiEditor"
    "CodeMirror"
    "SyntaxHighlight_GeSHi"
    "Scribunto"
    "TemplateStyles"
    "Cargo"
    "PageForms"
    "Linter"
    "DiscussionTools"
    "Echo"
    "Cite"
    "Translate"
    "UniversalLanguageSelector"
    "MultimediaViewer"
    "PageImages"
    "TextExtracts"
    "AbuseFilter"
    "ConfirmEdit"
    "AdvancedSearch"
)

echo "==> Downloading Wikimedia Extensions..."
for ext in "${WIKIMEDIA_EXTENSIONS[@]}"; do
    TARGET="${EXT_DIR}/${ext}"
    if [ ! -d "${TARGET}" ]; then
        echo "--> Fetching extension: ${ext}"
        git clone --depth 1 "https://gerrit.wikimedia.org/r/mediawiki/extensions/${ext}.git" -b "${MW_BRANCH}" "${TARGET}" 2>/dev/null || \
        git clone --depth 1 "https://gerrit.wikimedia.org/r/mediawiki/extensions/${ext}.git" "${TARGET}" 2>/dev/null || \
        git clone --depth 1 "https://github.com/wikimedia/mediawiki-extensions-${ext}.git" "${TARGET}" 2>/dev/null || \
        echo "Warning: Could not clone ${ext}"
    else
        echo "--> Extension ${ext} already exists, skipping."
    fi
done

# Third-party extensions
if [ ! -d "${EXT_DIR}/TabberNeue" ]; then
    echo "--> Fetching extension: TabberNeue"
    git clone --depth 1 https://github.com/Universal-Omega/TabberNeue.git "${EXT_DIR}/TabberNeue" 2>/dev/null || \
    git clone --depth 1 https://gerrit.wikimedia.org/r/mediawiki/extensions/TabberNeue.git "${EXT_DIR}/TabberNeue" 2>/dev/null || true
fi

# Run composer install for extensions requiring composer dependencies (like Scribunto, Translate, etc.)
for ext in "Scribunto" "Translate"; do
    if [ -f "${EXT_DIR}/${ext}/composer.json" ]; then
        echo "--> Running composer install in ${EXT_DIR}/${ext}..."
        (cd "${EXT_DIR}/${ext}" && composer install --no-dev --optimize-autoloader --no-interaction) || true
    fi
done

echo "==> All Parch Wiki extensions and skins initialized successfully."
