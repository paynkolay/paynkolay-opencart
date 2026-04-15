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
  echo "Installing OpenCart 4.x..."

  php /var/www/html/install/cli_install.php install \
    --db_hostname db \
    --db_username opencart \
    --db_password opencart \
    --db_database opencart4x \
    --db_driver mysqli \
    --db_port 3306 \
    --username admin \
    --password admin \
    --email admin@test.com \
    --http_server "http://localhost:8040/" 2>&1 || true

  rm -rf /var/www/html/install
  chown -R www-data:www-data /var/www/html
  touch /var/www/html/.installed
  echo "OpenCart 4.x installed! http://localhost:8040"
fi

# Register PayNKolay plugin (idempotent — runs every startup)
echo "Registering PayNKolay plugin..."
mysql -h db -u opencart -popencart opencart4x << 'SQL' 2>/dev/null || true
INSERT INTO oc_extension (extension, type, code)
SELECT 'nkolay', 'payment', 'nkolay' FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_extension WHERE type='payment' AND code='nkolay');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolay', 'payment_nkolay_status', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolay_status');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolay', 'payment_nkolay_sort_order', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolay_sort_order');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolay', 'payment_nkolaypos_mode', '1', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_mode');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolay', 'payment_nkolaypos_type', '3D', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_type');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolay', 'payment_nkolaypos_order_status_id', '5', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_order_status_id');
SQL

# Grant admin permissions
mysql -h db -u opencart -popencart opencart4x -e "
UPDATE oc_user_group
SET permission = JSON_ARRAY_APPEND(
  JSON_ARRAY_APPEND(permission, '\$.access', 'extension/nkolay/payment/nkolay'),
  '\$.modify', 'extension/nkolay/payment/nkolay'
)
WHERE user_group_id = 1
AND JSON_SEARCH(permission, 'one', 'extension/nkolay/payment/nkolay', NULL, '\$.access') IS NULL;
" 2>/dev/null || true
echo "PayNKolay plugin registered."

wait $APACHE_PID
