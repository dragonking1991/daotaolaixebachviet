#!/usr/bin/env bash
set -euo pipefail

# Quick regression for legacy Excel imports: gplx, gxn, qr.
# Usage: bash scripts/regression_import_gplx_gxn_qr.sh

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

if ! command -v docker >/dev/null 2>&1; then
  echo "[FAIL] docker command not found"
  exit 1
fi

TS="$(date +%s)"
C_GPLX="9901${TS}001"
C_GXN="9901${TS}002"
C_QR="9901${TS}003"
COOKIE_FILE="/tmp/bv_admin_cookie_regression.txt"

cleanup() {
  docker compose exec db mysql -udaotaola6686_db -plocalpass123 -e "USE daotaola6686_db; DELETE FROM table_product WHERE (type='gplx' AND tenvi='Regression GPLX') OR (type='gxn' AND tenvi='Regression GXN') OR (type='qr' AND tenvi='Regression QR');" >/dev/null 2>&1 || true
  rm -f "$COOKIE_FILE"
}
trap cleanup EXIT

# Ensure stale regression rows from previous runs do not affect current assertions.
cleanup

echo "[1/6] Generate regression fixtures in web container"
docker compose exec -e C_GPLX="$C_GPLX" -e C_GXN="$C_GXN" -e C_QR="$C_QR" web php -r '
require "/var/www/html/libraries/PHPExcel.php";
require_once "/var/www/html/libraries/PHPExcel/IOFactory.php";
function mk($path,$row){$e=new PHPExcel();$s=$e->getActiveSheet();$h=["STT","Ten","Ngay sinh","CCCD","Cot4","Cot5","Cot6"];foreach($h as $i=>$v){$s->setCellValueByColumnAndRow($i,1,$v);}foreach($row as $i=>$v){$s->setCellValueByColumnAndRow($i,2,$v);}PHPExcel_IOFactory::createWriter($e,"Excel2007")->save($path);} 
$b="/var/www/html/verification-fixtures";
mk($b."/reg_gplx.xlsx",[1,"Regression GPLX","1990-01-01",getenv("C_GPLX"),"B2","1500000",""]);
mk($b."/reg_gxn.xlsx",[1,"Regression GXN","1991-02-02",getenv("C_GXN"),"B1","KHOA-TEST","GXN-TEST-001"]);
mk($b."/reg_qr.xlsx",[1,"Regression QR","1992-03-03",getenv("C_QR"),"C","900000",""]);
'

echo "[2/6] Login admin"
rm -f "$COOKIE_FILE"
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -X POST 'http://localhost:8080/admin/ajax/ajax_login.php' -d 'username=admin&password=admin123' >/dev/null

echo "[3/6] Import gplx"
curl -s -L -c "$COOKIE_FILE" -b "$COOKIE_FILE" -F 'importExcel=1' -F 'file-excel=@verification-fixtures/reg_gplx.xlsx;type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 'http://localhost:8080/admin/index.php?com=import&act=uploadExcel&type=gplx' >/dev/null

echo "[4/6] Import gxn"
curl -s -L -c "$COOKIE_FILE" -b "$COOKIE_FILE" -F 'importExcel=1' -F 'file-excel=@verification-fixtures/reg_gxn.xlsx;type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 'http://localhost:8080/admin/index.php?com=import&act=uploadExcel&type=gxn' >/dev/null

echo "[5/6] Import qr"
curl -s -L -c "$COOKIE_FILE" -b "$COOKIE_FILE" -F 'importExcel=1' -F 'file-excel=@verification-fixtures/reg_qr.xlsx;type=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 'http://localhost:8080/admin/index.php?com=import&act=uploadExcel&type=qr' >/dev/null

echo "[6/6] Validate PASS/FAIL"
RESULTS="$(docker compose exec db mysql -N -B -udaotaola6686_db -plocalpass123 -e "USE daotaola6686_db; SELECT IF(COUNT(*)=1 AND MAX(gplx)='B2' AND MAX(gia)=1500000,'PASS','FAIL') FROM table_product WHERE type='gplx' AND tenvi='Regression GPLX'; SELECT IF(COUNT(*)=1 AND MAX(hang)='B1' AND MAX(khoa)='KHOA-TEST' AND MAX(gxn)='GXN-TEST-001','PASS','FAIL') FROM table_product WHERE type='gxn' AND tenvi='Regression GXN'; SELECT IF(COUNT(*)=1 AND MAX(hang)='C' AND MAX(gia)=900000,'PASS','FAIL') FROM table_product WHERE type='qr' AND tenvi='Regression QR';" | tr -d '\r')"

GPLX_STATUS="$(echo "$RESULTS" | sed -n '1p')"
GXN_STATUS="$(echo "$RESULTS" | sed -n '2p')"
QR_STATUS="$(echo "$RESULTS" | sed -n '3p')"

echo "gplx: $GPLX_STATUS"
echo "gxn : $GXN_STATUS"
echo "qr  : $QR_STATUS"

if [[ "$GPLX_STATUS" == "PASS" && "$GXN_STATUS" == "PASS" && "$QR_STATUS" == "PASS" ]]; then
  echo "[PASS] Regression import gplx/gxn/qr"
  exit 0
fi

echo "[FAIL] Regression import gplx/gxn/qr"
exit 1
