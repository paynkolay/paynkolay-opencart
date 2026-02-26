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

wait $APACHE_PID
