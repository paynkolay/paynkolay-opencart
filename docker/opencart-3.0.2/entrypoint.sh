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
  echo "Installing OpenCart 3.0.2.0..."

  php /var/www/html/install/cli_install.php install \
    --db_hostname db \
    --db_username opencart \
    --db_password opencart \
    --db_database opencart302 \
    --db_driver mysqli \
    --db_port 3306 \
    --username admin \
    --password admin \
    --email admin@test.com \
    --http_server "http://localhost:8032/" 2>&1 || true

  rm -rf /var/www/html/install
  chown -R www-data:www-data /var/www/html
  touch /var/www/html/.installed
  echo "OpenCart 3.0.2.0 installed! http://localhost:8032"
fi

# Register PayNKolay plugin (idempotent — runs every startup)
echo "Registering PayNKolay plugin..."
mysql --skip-ssl -h db -u opencart -popencart opencart302 << 'SQL' || true
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

-- Public UAT credentials (test environment only)
INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_sx', '118591467|bScbGDYCtPf7SS1N6PQ6/+58rFhW1WpsWINqvkJFaJlu6bMH2tgPKDQtjeA5vClpzJP24uA0vx7OX53cP3SgUspa4EvYix+1C3aXe++8glUvu9Oyyj3v300p5NP7ro/9K57Zcw==', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_sx');

INSERT INTO oc_setting (store_id, code, `key`, value, serialized)
SELECT 0, 'payment_nkolaypos', 'payment_nkolaypos_secret', '_YckdxUbv4vrnMUZ6VQsr', 0 FROM dual
WHERE NOT EXISTS (SELECT 1 FROM oc_setting WHERE `key`='payment_nkolaypos_secret');
SQL

# Grant admin permissions
mysql --skip-ssl -h db -u opencart -popencart opencart302 -e "
UPDATE oc_user_group
SET permission = JSON_ARRAY_APPEND(
  JSON_ARRAY_APPEND(permission, '\$.access', 'extension/payment/nkolaypos'),
  '\$.modify', 'extension/payment/nkolaypos'
)
WHERE user_group_id = 1
AND JSON_SEARCH(permission, 'one', 'extension/payment/nkolaypos', NULL, '\$.access') IS NULL;
" 2>/dev/null || true
echo "PayNKolay plugin registered."

wait $APACHE_PID
