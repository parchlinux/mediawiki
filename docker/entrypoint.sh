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
MW_ADMIN_PASSWORD="${MEDIAWIKI_PASSWORD:-parchpassword123}"
MW_SERVER="${MW_SERVER:-http://localhost}"
MW_SITENAME="${MW_SITENAME:-Parch Linux Wiki}"
MW_LANG="${MW_LANG:-en}"

echo "==> Starting Parch Linux MediaWiki container initialization..."

# Ensure core vendor dependencies exist (handles volume overrides)
if [ ! -f "${MW_INSTALL_PATH}/vendor/autoload.php" ] && [ -f "${MW_INSTALL_PATH}/composer.json" ]; then
    echo "--> Installing missing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --working-dir="${MW_INSTALL_PATH}" || true
fi

# Ensure extension composer dependencies exist
for composer_file in "${MW_INSTALL_PATH}/extensions"/*/composer.json "${MW_INSTALL_PATH}/skins"/*/composer.json; do
    if [ -f "${composer_file}" ]; then
        dir="$(dirname "${composer_file}")"
        if [ ! -f "${dir}/vendor/autoload.php" ]; then
            echo "--> Installing missing extension dependencies for $(basename "${dir}")..."
            (cd "${dir}" && composer install --no-dev --optimize-autoloader --no-interaction) || true
        fi
    fi
done

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

# Check database schema state and auto-initialize if clean
if [ "${AUTO_UPDATE_DB:-true}" = "true" ] && [ -f "${MW_INSTALL_PATH}/maintenance/run.php" ]; then
    HAS_TABLES=false
    if command -v mariadb >/dev/null 2>&1; then
        TABLE_CHECK=$(mariadb -h "${MW_DB_SERVER}" -P "${MW_DB_PORT}" -u "${MW_DB_USER}" -p"${MW_DB_PASSWORD}" "${MW_DB_NAME}" -e "SHOW TABLES LIKE 'site_stats';" -N 2>/dev/null || true)
        if [ -n "${TABLE_CHECK}" ]; then
            HAS_TABLES=true
        fi
    fi

    if [ "${HAS_TABLES}" = "false" ]; then
        echo "--> Clean database detected. Running initial MediaWiki installer..."
        if [ -f "${MW_INSTALL_PATH}/LocalSettings.php" ]; then
            mv "${MW_INSTALL_PATH}/LocalSettings.php" "${MW_INSTALL_PATH}/LocalSettings.php.tmp"
        fi

        php "${MW_INSTALL_PATH}/maintenance/run.php" install.php \
            --dbserver="${MW_DB_SERVER}" \
            --dbport="${MW_DB_PORT}" \
            --dbname="${MW_DB_NAME}" \
            --dbuser="${MW_DB_USER}" \
            --dbpass="${MW_DB_PASSWORD}" \
            --server="${MW_SERVER}" \
            --scriptpath="${MW_SCRIPT_PATH:-}" \
            --lang="${MW_LANG}" \
            --pass="${MW_ADMIN_PASSWORD}" \
            --confpath="/tmp" \
            "${MW_SITENAME}" "${MW_ADMIN_USER}" || true

        if [ -f "${MW_INSTALL_PATH}/LocalSettings.php.tmp" ]; then
            mv "${MW_INSTALL_PATH}/LocalSettings.php.tmp" "${MW_INSTALL_PATH}/LocalSettings.php"
        fi
    fi

    echo "--> Checking and applying database schema updates..."
    php "${MW_INSTALL_PATH}/maintenance/run.php" update.php --quick || true
fi

echo "==> MediaWiki ready. Executing command: $@"
exec "$@"
