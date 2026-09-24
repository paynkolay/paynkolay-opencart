#!/bin/bash
set -e

apache2-foreground &
APACHE_PID=$!

echo "Waiting for database..."
until mysqladmin ping -h db -u opencart -popencart --silent 2>/dev/null; do
  sleep 2
done
echo "Database ready."

if [ ! -f /var/www/html/.installed ]; then
  echo "Installing OpenCart 2.3..."

  # Write configs manually (CLI installer fails on mcrypt check)
  cat > /var/www/html/config.php << 'PHPEOF'
<?php
define('HTTP_SERVER', 'http://localhost:8023/');
define('HTTPS_SERVER', 'http://localhost:8023/');
define('DIR_APPLICATION', '/var/www/html/catalog/');
define('DIR_SYSTEM', '/var/www/html/system/');
define('DIR_IMAGE', '/var/www/html/image/');
define('DIR_STORAGE', '/var/www/html/system/storage/');
define('DIR_LANGUAGE', '/var/www/html/catalog/language/');
define('DIR_TEMPLATE', '/var/www/html/catalog/view/theme/');
define('DIR_CONFIG', '/var/www/html/system/config/');
define('DIR_CACHE', '/var/www/html/system/storage/cache/');
define('DIR_DOWNLOAD', '/var/www/html/system/storage/download/');
define('DIR_LOGS', '/var/www/html/system/storage/logs/');
define('DIR_MODIFICATION', '/var/www/html/system/storage/modification/');
define('DIR_UPLOAD', '/var/www/html/system/storage/upload/');
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'db');
define('DB_USERNAME', 'opencart');
define('DB_PASSWORD', 'opencart');
define('DB_DATABASE', 'opencart23');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
PHPEOF

  cat > /var/www/html/admin/config.php << 'PHPEOF'
<?php
define('HTTP_SERVER', 'http://localhost:8023/admin/');
define('HTTPS_SERVER', 'http://localhost:8023/admin/');
define('HTTP_CATALOG', 'http://localhost:8023/');
define('HTTPS_CATALOG', 'http://localhost:8023/');
define('DIR_APPLICATION', '/var/www/html/admin/');
define('DIR_SYSTEM', '/var/www/html/system/');
define('DIR_IMAGE', '/var/www/html/image/');
define('DIR_STORAGE', '/var/www/html/system/storage/');
define('DIR_CATALOG', '/var/www/html/catalog/');
define('DIR_LANGUAGE', '/var/www/html/admin/language/');
define('DIR_TEMPLATE', '/var/www/html/admin/view/template/');
define('DIR_CONFIG', '/var/www/html/system/config/');
define('DIR_CACHE', '/var/www/html/system/storage/cache/');
define('DIR_DOWNLOAD', '/var/www/html/system/storage/download/');
define('DIR_LOGS', '/var/www/html/system/storage/logs/');
define('DIR_MODIFICATION', '/var/www/html/system/storage/modification/');
define('DIR_UPLOAD', '/var/www/html/system/storage/upload/');
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'db');
define('DB_USERNAME', 'opencart');
define('DB_PASSWORD', 'opencart');
define('DB_DATABASE', 'opencart23');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
PHPEOF

  # Import database schema
  mysql --skip-ssl -h db -u opencart -popencart opencart23 < /var/www/html/install/opencart.sql 2>/dev/null || true

  # Create admin user
  mysql --skip-ssl -h db -u opencart -popencart opencart23 -e "
    DELETE FROM oc_user WHERE username='admin';
    INSERT INTO oc_user SET user_id=1, user_group_id=1, username='admin',
      password=SHA1(CONCAT('', 'admin')), salt='',
      firstname='Admin', lastname='Admin', email='admin@test.com',
      status=1, date_added=NOW();
  " 2>/dev/null || true

  # Create required storage directories
  mkdir -p /var/www/html/system/storage/{cache,download,logs,modification,upload,session}

  rm -rf /var/www/html/install
  chown -R www-data:www-data /var/www/html
  touch /var/www/html/.installed
  echo "OpenCart 2.3 installed! http://localhost:8023"
fi

# Register paynkolay plugin (idempotent — runs every startup)
echo "Registering paynkolay plugin..."
mysql --skip-ssl -h db -u opencart -popencart opencart23 << 'SQL' || true
INSERT INTO oc_extension (type, code)
SELECT 'payment', 'nkolaypos' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_extension WHERE type='payment' AND code='nkolaypos');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_status', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_status');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_sort_order', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_sort_order');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_mode', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_mode');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_type', '3D', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_type');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_order_status_id', '5', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_order_status_id');

SQL

# Grant admin permissions
mysql --skip-ssl -h db -u opencart -popencart opencart23 -e "
UPDATE oc_user_group
SET permission = JSON_ARRAY_APPEND(
  JSON_ARRAY_APPEND(permission, '\$.access', 'extension/payment/nkolaypos'),
  '\$.modify', 'extension/payment/nkolaypos'
)
WHERE user_group_id = 1
AND JSON_SEARCH(permission, 'one', 'extension/payment/nkolaypos', NULL, '\$.access') IS NULL;
" 2>/dev/null || true
echo "paynkolay plugin registered."

wait $APACHE_PID
