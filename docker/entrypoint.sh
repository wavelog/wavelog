#!/bin/bash
set -e

# Ensure .htaccess exists. In dev env this might be missing
[ -f /var/www/html/.htaccess ] || cp /var/www/html/htaccess.sample /var/www/html/.htaccess

# If config.php and database.php exist, we can remove the install directory to save space and reduce attack surface.
# Set WAVELOG_SKIP_INSTALL_DELETE to a truthy value (1/true/yes/on) to keep the install directory.
case "${WAVELOG_SKIP_INSTALL_DELETE,,}" in
	1|true|yes|on) skip_install_delete=1 ;;
	*)             skip_install_delete=0 ;;
esac

if [ -f "/var/www/html/application/config/$CI_ENV/config.php" ] \
	&& [ -f "/var/www/html/application/config/$CI_ENV/database.php" ] \
	&& [ "$skip_install_delete" -eq 0 ]; then
	echo "INFO: config.php and database.php found, removing install directory"
	rm -rf /var/www/html/install
fi

# Set PHP Settings
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-256M}
PHP_UPLOAD_MAX_FILESIZE=${PHP_UPLOAD_MAX_FILESIZE:-64M}
PHP_POST_MAX_SIZE=${PHP_POST_MAX_SIZE:-64M}
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-600}

cat > "$PHP_INI_DIR/conf.d/wavelog.ini" <<EOF
file_uploads = On
memory_limit = ${PHP_MEMORY_LIMIT}
upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}
post_max_size = ${PHP_POST_MAX_SIZE}
max_execution_time = ${PHP_MAX_EXECUTION_TIME}
EOF

# Optionally remap the www-data user/group to a host-provided UID/GID so that
# bind-mount users (e.g. Unraid/NAS) can align file ownership with their host.
# When PUID/PGID are unset the image behaves exactly as before (www-data = 33:33,
# no remap and no chown run at all).
if [ -n "$PUID" ] || [ -n "$PGID" ]; then
	: "${PUID:=33}"
	: "${PGID:=33}"

	groupmod --non-unique --gid "$PGID" www-data
	usermod  --non-unique --uid "$PUID" www-data

	WRITABLE_DIRS=(
		"/var/www/html/application/cache"
		"/var/www/html/application/config"
		"/var/www/html/application/logs"
		"/var/www/html/assets"
		"/var/www/html/backup"
		"/var/www/html/updates"
		"/var/www/html/uploads"
		"/var/www/html/userdata"
		"/var/www/html/images/eqsl_card_images"
		"/var/www/html/install"
	)

	# Re-apply the build-time ownership model (root:www-data) with the new gid so
	# group-write keeps working for the remapped worker. Owner stays root, so the
	# hardening model is preserved. The gid guard skips dirs that already match:
	# this leaves correctly-owned bind mounts untouched and keeps restarts fast on
	# large volumes.
	for dir in "${WRITABLE_DIRS[@]}"; do
		if [ -d "$dir" ] && [ "$(stat -c '%g' "$dir")" != "$PGID" ]; then
			chown -R root:www-data "$dir"
		fi
	done
fi

exec "$@"
