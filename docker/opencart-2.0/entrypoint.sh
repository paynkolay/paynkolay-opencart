#!/bin/bash
set -e

# Start Apache in background
apache2-foreground &
APACHE_PID=$!

# Wait for database
echo "Waiting for database..."
until mysqladmin ping -h db -u opencart -popencart --silent 2>/dev/null; do
  sleep 2
done
echo "Database ready."

# Install OpenCart if not yet installed
if [ ! -f /var/www/html/.installed ]; then
  echo "Installing OpenCart 2.0..."
  php /var/www/html/install/index.php install \
    --db_hostname db \
    --db_username opencart \
    --db_password opencart \
    --db_database opencart20 \
    --db_driver mysqli \
    --db_port 3306 \
    --username admin \
    --password admin \
    --email admin@test.com \
    --http_server "http://localhost:8020/" 2>/dev/null || true

  # Write config manually if CLI install doesn't work for 2.0
  cat > /var/www/html/config.php << 'PHPEOF'
<?php
define('HTTP_SERVER', 'http://localhost:8020/');
define('HTTPS_SERVER', 'http://localhost:8020/');
define('DIR_APPLICATION', '/var/www/html/catalog/');
define('DIR_SYSTEM', '/var/www/html/system/');
define('DIR_DATABASE', '/var/www/html/system/database/');
define('DIR_LANGUAGE', '/var/www/html/catalog/language/');
define('DIR_TEMPLATE', '/var/www/html/catalog/view/theme/');
define('DIR_CONFIG', '/var/www/html/system/config/');
define('DIR_IMAGE', '/var/www/html/image/');
define('DIR_CACHE', '/var/www/html/system/cache/');
define('DIR_DOWNLOAD', '/var/www/html/system/download/');
define('DIR_LOGS', '/var/www/html/system/logs/');
define('DIR_MODIFICATION', '/var/www/html/system/modification/');
define('DIR_UPLOAD', '/var/www/html/system/upload/');
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'db');
define('DB_USERNAME', 'opencart');
define('DB_PASSWORD', 'opencart');
define('DB_DATABASE', 'opencart20');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
PHPEOF

  cat > /var/www/html/admin/config.php << 'PHPEOF'
<?php
define('HTTP_SERVER', 'http://localhost:8020/admin/');
define('HTTPS_SERVER', 'http://localhost:8020/admin/');
define('HTTP_CATALOG', 'http://localhost:8020/');
define('HTTPS_CATALOG', 'http://localhost:8020/');
define('DIR_APPLICATION', '/var/www/html/admin/');
define('DIR_SYSTEM', '/var/www/html/system/');
define('DIR_DATABASE', '/var/www/html/system/database/');
define('DIR_LANGUAGE', '/var/www/html/admin/language/');
define('DIR_TEMPLATE', '/var/www/html/admin/view/template/');
define('DIR_CONFIG', '/var/www/html/system/config/');
define('DIR_IMAGE', '/var/www/html/image/');
define('DIR_CACHE', '/var/www/html/system/cache/');
define('DIR_DOWNLOAD', '/var/www/html/system/download/');
define('DIR_LOGS', '/var/www/html/system/logs/');
define('DIR_MODIFICATION', '/var/www/html/system/modification/');
define('DIR_UPLOAD', '/var/www/html/system/upload/');
define('DIR_CATALOG', '/var/www/html/catalog/');
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'db');
define('DB_USERNAME', 'opencart');
define('DB_PASSWORD', 'opencart');
define('DB_DATABASE', 'opencart20');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
PHPEOF

  # Import database schema
  if [ -f /var/www/html/install/opencart.sql ]; then
    mysql -h db -u opencart -popencart opencart20 < /var/www/html/install/opencart.sql 2>/dev/null || true
  fi

  # Create admin user
  mysql -h db -u opencart -popencart opencart20 -e "
    DELETE FROM oc_user WHERE username='admin';
    INSERT INTO oc_user SET user_id=1, user_group_id=1, username='admin',
      password=SHA1(CONCAT('', 'admin')), salt='',
      firstname='Admin', lastname='Admin', email='admin@test.com',
      status=1, date_added=NOW();
  " 2>/dev/null || true

  rm -rf /var/www/html/install
  chown -R www-data:www-data /var/www/html
  touch /var/www/html/.installed
  echo "OpenCart 2.0 installed! http://localhost:8020"
fi

# Register PayNKolay plugin (idempotent — runs every startup)
echo "Registering PayNKolay plugin..."
mysql -h db -u opencart -popencart opencart20 << 'SQL' 2>/dev/null || true
INSERT INTO oc_extension (type, code)
SELECT 'payment', 'nkolaypos' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_extension WHERE type='payment' AND code='nkolaypos');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'nkolaypos', 'nkolaypos_status', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='nkolaypos_status');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'nkolaypos', 'nkolaypos_sort_order', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='nkolaypos_sort_order');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'nkolaypos', 'nkolaypos_mode', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='nkolaypos_mode');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'nkolaypos', 'nkolaypos_type', '3D', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='nkolaypos_type');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'nkolaypos', 'nkolaypos_order_status_id', '5', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='nkolaypos_order_status_id');
SQL
echo "PayNKolay plugin registered."

wait $APACHE_PID
