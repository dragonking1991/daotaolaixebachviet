#!/bin/bash
set -e

# Initialize MariaDB data directory if empty (Fly.io volume mount replaces build-time data)
if [ ! -d "/var/lib/mysql/mysql" ]; then
  echo "Initializing MariaDB data directory..."
  mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Ensure socket directory exists
mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld

# Start MariaDB
mysqld_safe &

# Wait for MySQL to be ready (up to 60 seconds)
for i in $(seq 1 60); do
  if mysqladmin ping -h localhost --silent 2>/dev/null; then
    echo "MariaDB is ready."
    break
  fi
  echo "Waiting for MariaDB... ($i)"
  sleep 1
done

# Create database and user if not exists
mysql -u root -e "CREATE DATABASE IF NOT EXISTS daotaola6686_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'daotaola6686_db'@'localhost' IDENTIFIED BY 'localpass123';"
mysql -u root -e "GRANT ALL PRIVILEGES ON daotaola6686_db.* TO 'daotaola6686_db'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Import SQL if tables do not exist yet
TABLE_COUNT=$(mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='daotaola6686_db';")
if [ "$TABLE_COUNT" -lt 5 ]; then
  echo "Importing database..."
  mysql -u root daotaola6686_db < /var/www/html/daotaola6686_db.sql 2>/dev/null || true
  mysql -u root daotaola6686_db < /var/www/html/migration_kysathach.sql 2>/dev/null || true
  echo "Database imported."
fi

# Start Apache in foreground
apache2-foreground
