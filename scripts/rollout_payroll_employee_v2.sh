#!/usr/bin/env bash
set -euo pipefail

# Production rollout helper for payroll employee import v2.
# Usage:
#   bash scripts/rollout_payroll_employee_v2.sh
# Optional env:
#   DB_SERVICE (default: db)
#   DB_USER (default: daotaola6686_db)
#   DB_PASS (default: localpass123)
#   DB_NAME (default: daotaola6686_db)

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

DB_SERVICE="${DB_SERVICE:-db}"
DB_USER="${DB_USER:-daotaola6686_db}"
DB_PASS="${DB_PASS:-localpass123}"
DB_NAME="${DB_NAME:-daotaola6686_db}"
STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$ROOT_DIR/backups"
BACKUP_FILE="$BACKUP_DIR/table_product_before_payroll_v2_${STAMP}.sql"

mkdir -p "$BACKUP_DIR"

echo "[1/6] Backup table_product -> $BACKUP_FILE"
docker compose exec "$DB_SERVICE" sh -lc "mysqldump --no-tablespaces -u$DB_USER -p$DB_PASS $DB_NAME table_product" > "$BACKUP_FILE"

echo "[2/6] Apply idempotent migration"
docker compose cp migration_payroll_employee.sql "$DB_SERVICE":/tmp/migration_payroll_employee.sql
docker compose exec "$DB_SERVICE" mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SOURCE /tmp/migration_payroll_employee.sql;"

echo "[3/6] Run backfill"
docker compose cp scripts/backfill_payroll_employee.sql "$DB_SERVICE":/tmp/backfill_payroll_employee.sql
docker compose exec "$DB_SERVICE" mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SOURCE /tmp/backfill_payroll_employee.sql;"

echo "[4/6] Verify schema/index"
docker compose exec "$DB_SERVICE" mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW COLUMNS FROM table_product LIKE 'ma_tra_cuu'; SHOW COLUMNS FROM table_product LIKE 'payroll_luong_thuc_nhan'; SHOW INDEX FROM table_product WHERE Key_name='idx_product_type_ma_tra_cuu';"

echo "[5/6] Verify data quality"
docker compose exec "$DB_SERVICE" mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT COUNT(*) AS total_nv FROM table_product WHERE type='nhan-vien'; SELECT COUNT(*) AS empty_ma_tra_cuu FROM table_product WHERE type='nhan-vien' AND (ma_tra_cuu='' OR ma_tra_cuu IS NULL); SELECT COUNT(*) AS has_net_salary FROM table_product WHERE type='nhan-vien' AND payroll_luong_thuc_nhan<>'';"

echo "[6/6] Run regression"
bash "$ROOT_DIR/scripts/regression_import_gplx_gxn_qr.sh"

echo "[DONE] Rollout + backfill completed successfully"
