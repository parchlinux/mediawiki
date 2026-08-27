#!/usr/bin/env bash
set -eo pipefail

# entrypoint.sh: Entrypoint script for Parch Linux MediaWiki container

MW_INSTALL_PATH="${MW_INSTALL_PATH:-/var/www/html}"
MW_DB_SERVER="${MW_DB_SERVER:-db}"
MW_DB_PORT="${MW_DB_PORT:-3306}"
MW_DB_NAME="${MW_DB_NAME:-parchwiki}"
MW_DB_USER="${MW_DB_USER:-wikiuser}"
MW_DB_PASSWORD="${MW_DB_PASSWORD:-wikipass}"
MW_ADMIN_USER="${MEDIAWIKI_USER:-Admin}"
MW_ADMIN_PASSWORD="${MEDIAWIKI_PASSWORD:-parchpass}"
MW_SERVER="${MW_SERVER:-http://localhost}"
MW_SITENAME="${MW_SITENAME:-Parch Linux Wiki}"
MW_LANG="${MW_LANG:-en}"

echo "==> Starting Parch Linux MediaWiki container initialization..."

# Ensure persistent directories exist and have proper permissions
mkdir -p "${MW_INSTALL_PATH}/images" "${MW_INSTALL_PATH}/cache"
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data "${MW_INSTALL_PATH}/images" "${MW_INSTALL_PATH}/cache"
    chmod -R 775 "${MW_INSTALL_PATH}/images" "${MW_INSTALL_PATH}/cache"
fi

# Wait for database if configured
if [ "${WAIT_FOR_DB:-true}" = "true" ] && [ -n "${MW_DB_SERVER}" ] && [ "${MW_DB_SERVER}" != "localhost" ]; then
    echo "--> Waiting for MariaDB/MySQL at ${MW_DB_SERVER}:${MW_DB_PORT}..."
    MAX_TRIES=60
    COUNT=0
    while ! nc -z -w 2 "${MW_DB_SERVER}" "${MW_DB_PORT}" 2>/dev/null; do
        COUNT=$((COUNT + 1))
        if [ "${COUNT}" -ge "${MAX_TRIES}" ]; then
            echo "ERROR: Timeout waiting for database server at ${MW_DB_SERVER}:${MW_DB_PORT}"
            exit 1
        fi
        sleep 1
    done
    echo "--> Database server is accessible."
fi

# Run database setup or schema update if requested
if [ "${AUTO_UPDATE_DB:-true}" = "true" ] && [ -f "${MW_INSTALL_PATH}/maintenance/run.php" ]; then
    echo "--> Checking and applying database schema updates..."
    php "${MW_INSTALL_PATH}/maintenance/run.php" update.php --quick || {
        echo "--> Schema update exited with notice. If this is a clean database, please run installer."
    }
fi

echo "==> MediaWiki ready. Executing command: $@"
exec "$@"
