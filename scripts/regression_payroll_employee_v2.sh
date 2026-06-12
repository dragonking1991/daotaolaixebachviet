#!/usr/bin/env bash
set -euo pipefail

# Regression for payroll employee import v2 using verification-fixtures/LUONG.xlsm.
# Usage: bash scripts/regression_payroll_employee_v2.sh

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

DB_SERVICE="${DB_SERVICE:-db}"
WEB_SERVICE="${WEB_SERVICE:-web}"
DB_USER="${DB_USER:-daotaola6686_db}"
DB_PASS="${DB_PASS:-localpass123}"
DB_NAME="${DB_NAME:-daotaola6686_db}"
FIXTURE_REL="${FIXTURE_REL:-verification-fixtures/LUONG.xlsm}"
FIXTURE_HOST="$ROOT_DIR/$FIXTURE_REL"
FIXTURE_CONTAINER="/var/www/html/$FIXTURE_REL"
COOKIE_FILE="/tmp/bv_admin_cookie_payroll_regression.txt"
RUN_TS="$(date +%s)"

fail() {
	echo "[FAIL] $1"
	exit 1
}

cleanup() {
	rm -f "$COOKIE_FILE"
}
trap cleanup EXIT

money_to_int() {
	local raw="${1:-}"
	raw="$(printf '%s' "$raw" | tr -d '\r' | xargs)"
	if [[ -z "$raw" ]]; then
		echo 0
		return
	fi
	awk -v value="$raw" 'BEGIN {
		if (value ~ /^-?[0-9]{1,3}([.,][0-9]{3})+$/) {
			gsub(/[.,]/, "", value)
			printf "%.0f", value + 0
			exit
		}
		comma_count = gsub(/,/, "&", value)
		dot_count = gsub(/\./, "&", value)
		if (comma_count > 1) {
			gsub(/,/, "", value)
			printf "%.0f", value + 0
			exit
		}
		if (dot_count > 1) {
			gsub(/\./, "", value)
			printf "%.0f", value + 0
			exit
		}
		gsub(/\./, "", value)
		gsub(/,/, ".", value)
		if (value == "") value = 0
		printf "%.0f", value + 0
	}'
}

format_vn_int() {
	local value="${1:-0}"
	if [[ "$value" == "" || "$value" == "0" ]]; then
		echo "-"
		return
	fi
	printf '%s' "$value" | rev | sed 's/\([0-9][0-9][0-9]\)/\1./g' | rev | sed 's/^\.//'
}

format_vn_money() {
	local raw="${1:-}"
	raw="$(printf '%s' "$raw" | tr -d '\r' | xargs)"
	if [[ -z "$raw" || "$raw" == "0" ]]; then
		echo "-"
		return
	fi
	format_vn_int "$(money_to_int "$raw")"
}

if ! command -v docker >/dev/null 2>&1; then
	fail "docker command not found"
fi

if [[ ! -f "$FIXTURE_HOST" ]]; then
	fail "fixture not found: $FIXTURE_HOST"
fi

echo "[1/6] Ensure payroll schema exists"
HAS_PAYROLL_SCHEMA="$(docker compose exec -T "$DB_SERVICE" mysql -N -B -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW COLUMNS FROM table_product LIKE 'payroll_department';" 2>/dev/null || true)"
if [[ -z "$HAS_PAYROLL_SCHEMA" ]]; then
	docker compose cp migration_payroll_employee.sql "$DB_SERVICE":/tmp/migration_payroll_employee.sql >/dev/null
	docker compose exec -T "$DB_SERVICE" mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SOURCE /tmp/migration_payroll_employee.sql;" >/dev/null
fi

echo "[2/6] Derive expected department split from sheet L"
EXPECTED_COUNTS="$(docker compose exec -T -e FIXTURE_CONTAINER="$FIXTURE_CONTAINER" "$WEB_SERVICE" php <<'PHP'
<?php
require '/var/www/html/libraries/PHPExcel.php';
require_once '/var/www/html/libraries/PHPExcel/IOFactory.php';

function normalizeImportHeaderLabel($label)
{
	$label = strtolower(trim((string)$label));
	$search = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	$label = str_replace($search, $replace, $label);
	return preg_replace('/[^a-z0-9]+/', '', $label);
}

function getImportCellStringValue($worksheet, $column, $row)
{
	$value = $worksheet->getCellByColumnAndRow($column, $row)->getFormattedValue();
	if (is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
	return trim((string)$value);
}

function findImportHeaderMapColumnIndex($headerMap, $aliases)
{
	foreach ($aliases as $alias) {
		if (isset($headerMap[$alias])) return $headerMap[$alias];
	}
	return null;
}

function detectEmployeeDepartmentFromRow($rowValues)
{
	$nonEmptyValues = array();
	foreach ($rowValues as $value) {
		$value = trim((string)$value);
		if ($value !== '') $nonEmptyValues[] = $value;
	}
	if (count($nonEmptyValues) !== 1) return '';
	$department = $nonEmptyValues[0];
	if (stripos(normalizeImportHeaderLabel($department), 'bophan') === 0) return $department;
	return '';
}

function isEmployeeDepartmentSummaryRow($employeeName, $employeeOrder)
{
	$employeeName = trim((string)$employeeName);
	if ($employeeName === '') return false;
	$employeeOrder = trim((string)$employeeOrder);
	if ($employeeOrder !== '' && is_numeric($employeeOrder)) return false;
	$normalizedName = normalizeImportHeaderLabel($employeeName);
	foreach (array('bophan', 'phongban', 'khoi', 'nhanvien', 'bangiamdoc') as $prefix) {
		if (stripos($normalizedName, $prefix) === 0) return true;
	}
	return false;
}

function getEmployeeRowValueByAliases($rowDetail, $aliases)
{
	if (!is_array($rowDetail) || empty($rowDetail)) return '';
	foreach ($aliases as $alias) {
		if (isset($rowDetail[$alias])) {
			$value = trim((string)$rowDetail[$alias]);
			if ($value !== '') return $value;
		}
	}
	return '';
}

function getEmployeeImportColumnIndex($worksheet, $fallbackHighestColumnIndex, $headerRow)
{
	$maxScanColumns = min((int)$fallbackHighestColumnIndex, 1000);
	$lastHeaderColumn = 0;
	$consecutiveEmpty = 0;
	$foundHeader = false;
	for ($column = 0; $column < $maxScanColumns; $column++) {
		$headerValue = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $headerRow));
		if ($headerValue !== '') {
			$foundHeader = true;
			$lastHeaderColumn = $column + 1;
			$consecutiveEmpty = 0;
		} elseif ($foundHeader) {
			$consecutiveEmpty++;
			if ($consecutiveEmpty >= 30) break;
		}
	}
	if ($lastHeaderColumn <= 0) return min((int)$fallbackHighestColumnIndex, 60);
	return $lastHeaderColumn;
}

function detectEmployeeHeaderRow($worksheet, $highestRow, $fallbackHighestColumnIndex)
{
	$maxScanRows = min((int)$highestRow, 20);
	if ($maxScanRows <= 0) return 12;
	$targetAliases = array('stt','hovaten','hoten','ten','chucvu','songaylamviec','luongchinh','thuongletet','tiencom','phucapxangxe','dayltsathach','chieusinhtttn','khacdtkhac','lamthemgio','dienthoai','tongthunhap','nldnopbhxh105','ttnopbhxh215','thunhapchiuthue','giamtrugiacanh','sonpt','nguoiphuthuoc','thunhaptinhthue','bac','thuetncn','luongthucnhan','nghiavugv');
	$bestRow = 12;
	$bestScore = 0;
	for ($row = 1; $row <= $maxScanRows; $row++) {
		$scanColumns = getEmployeeImportColumnIndex($worksheet, $fallbackHighestColumnIndex, $row);
		$headerKeys = array();
		for ($column = 0; $column < $scanColumns; $column++) {
			$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $row));
			if ($headerKey === '') continue;
			$headerKeys[$headerKey] = true;
		}
		if (empty($headerKeys)) continue;
		$score = 0;
		foreach ($targetAliases as $alias) {
			if (isset($headerKeys[$alias])) $score++;
		}
		if (isset($headerKeys['hovaten']) || isset($headerKeys['hoten']) || isset($headerKeys['ten'])) $score += 5;
		if (isset($headerKeys['chucvu'])) $score += 2;
		if (isset($headerKeys['luongthucnhan'])) $score += 2;
		if ($score > $bestScore) {
			$bestScore = $score;
			$bestRow = $row;
		}
	}
	if ($bestScore <= 0) return 12;
	return $bestRow;
}

$fixture = getenv('FIXTURE_CONTAINER');
$workbook = PHPExcel_IOFactory::load($fixture);
$sheet = $workbook->getSheetByName('L');
if (!$sheet) {
	fwrite(STDERR, "missing sheet L\n");
	exit(1);
}

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
$headerRow = detectEmployeeHeaderRow($sheet, $highestRow, $highestColumnIndex);
$employeeColumnIndex = getEmployeeImportColumnIndex($sheet, $highestColumnIndex, $headerRow);
$dataStartRow = $headerRow + 1;

$headerMap = array();
for ($column = 0; $column < $employeeColumnIndex; $column++) {
	$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($sheet, $column, $headerRow));
	if ($headerKey === '') continue;
	if (!isset($headerMap[$headerKey])) $headerMap[$headerKey] = $column;
}

$nameColumn = findImportHeaderMapColumnIndex($headerMap, array('hovaten', 'hoten', 'ten'));
$orderColumn = findImportHeaderMapColumnIndex($headerMap, array('stt'));
if ($nameColumn === null) {
	fwrite(STDERR, "missing employee name column\n");
	exit(1);
}

$currentDepartment = '';
$isGiaoVienSection = false;
$vanPhong = 0;
$giaoVien = 0;

for ($row = $dataStartRow; $row <= $highestRow; $row++) {
	$rowDetail = array();
	for ($column = 0; $column < $employeeColumnIndex; $column++) {
		$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($sheet, $column, $headerRow));
		if ($headerKey === '') $headerKey = 'cot'.($column + 1);
		$rowDetail[$headerKey] = getImportCellStringValue($sheet, $column, $row);
	}

	$departmentFromRow = detectEmployeeDepartmentFromRow($rowDetail);
	if ($departmentFromRow !== '') {
		$currentDepartment = $departmentFromRow;
		if (!$isGiaoVienSection && stripos(normalizeImportHeaderLabel($departmentFromRow), 'bophangiaovien') !== false) $isGiaoVienSection = true;
		continue;
	}

	$employeeName = getImportCellStringValue($sheet, $nameColumn, $row);
	$employeeOrder = ($orderColumn !== null) ? getImportCellStringValue($sheet, $orderColumn, $row) : '';
	if (isEmployeeDepartmentSummaryRow($employeeName, $employeeOrder)) {
		$currentDepartment = $employeeName;
		if (!$isGiaoVienSection && stripos(normalizeImportHeaderLabel($employeeName), 'bophangiaovien') !== false) $isGiaoVienSection = true;
		continue;
	}
	if ($employeeName === '') continue;

	$payrollBaseSalary = getEmployeeRowValueByAliases($rowDetail, array('luongchinh'));
	$payrollTotalIncome = getEmployeeRowValueByAliases($rowDetail, array('tongthunhap'));
	$payrollNetSalary = getEmployeeRowValueByAliases($rowDetail, array('luongthucnhan', 'luongthycnhan'));
	$hasPayrollSignals = ($payrollBaseSalary !== '' || $payrollTotalIncome !== '' || $payrollNetSalary !== '');
	if (($employeeOrder === '' || !is_numeric($employeeOrder)) && !$hasPayrollSignals) continue;

	if ($isGiaoVienSection) $giaoVien++;
	else $vanPhong++;
}

echo 'van_phong='.$vanPhong.PHP_EOL;
echo 'giao_vien='.$giaoVien.PHP_EOL;
PHP
)"

EXPECTED_VP="$(printf '%s\n' "$EXPECTED_COUNTS" | awk -F= '/^van_phong=/{print $2}')"
EXPECTED_GV="$(printf '%s\n' "$EXPECTED_COUNTS" | awk -F= '/^giao_vien=/{print $2}')"
if [[ -z "$EXPECTED_VP" || -z "$EXPECTED_GV" ]]; then
	fail "could not derive expected payroll counts from fixture"
fi

echo "[3/6] Login admin and import fixture"
rm -f "$COOKIE_FILE"
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -X POST 'http://localhost:8080/admin/ajax/ajax_login.php' -d 'username=admin&password=admin123' >/dev/null
IMPORT_RESPONSE="$(curl -s -L -c "$COOKIE_FILE" -b "$COOKIE_FILE" -F 'importExcel=1' -F "file-excel=@${FIXTURE_HOST};type=application/vnd.ms-excel.sheet.macroEnabled.12" 'http://localhost:8080/admin/index.php?com=import&act=uploadExcel&type=nhan-vien')"
if printf '%s' "$IMPORT_RESPONSE" | grep -Eq 'Không hỗ trợ kiểu tập tin này|Không tìm thấy sheet có tên "L"|Dữ liệu rỗng'; then
	fail "fixture import returned an error"
fi

echo "[4/6] Validate imported department counts"
ACTUAL_COUNTS="$(docker compose exec -T "$DB_SERVICE" mysql -N -B -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT COALESCE(SUM(payroll_department='van_phong'),0), COALESCE(SUM(payroll_department='giao_vien'),0) FROM table_product WHERE type='nhan-vien' AND JSON_UNQUOTE(JSON_EXTRACT(options2, '$.import_meta.file_name'))='LUONG.xlsm' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.import_meta.imported_at')) AS UNSIGNED) >= $RUN_TS;" | LC_ALL=C tr -d '\r')"
ACTUAL_VP="$(printf '%s' "$ACTUAL_COUNTS" | awk '{print $1}')"
ACTUAL_GV="$(printf '%s' "$ACTUAL_COUNTS" | awk '{print $2}')"

echo "expected van_phong=$EXPECTED_VP, giao_vien=$EXPECTED_GV"
echo "actual   van_phong=$ACTUAL_VP, giao_vien=$ACTUAL_GV"

if [[ "$EXPECTED_VP" != "$ACTUAL_VP" || "$EXPECTED_GV" != "$ACTUAL_GV" ]]; then
	fail "payroll department classification mismatch"
fi

echo "[5/6] Validate public teacher formulas for 3 sample lookups"
RATE_ROW="$(docker compose exec -T "$DB_SERVICE" mysql -N -B -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT MAX(CASE WHEN config_key='payroll_rate_td' THEN config_value END), MAX(CASE WHEN config_key='payroll_rate_ss' THEN config_value END), MAX(CASE WHEN config_key='payroll_rate_c1' THEN config_value END), MAX(CASE WHEN config_key='payroll_rate_ce' THEN config_value END) FROM table_payroll_config;" | LC_ALL=C tr -d '\r')"
read -r RATE_TD RATE_SS RATE_C1 RATE_CE <<<"$RATE_ROW"

SAMPLE_ROWS="$(docker compose exec -T "$DB_SERVICE" mysql -N -B -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT tenvi, ma_tra_cuu, COALESCE(payroll_td,0), COALESCE(payroll_ss,0), COALESCE(payroll_c1,0), COALESCE(payroll_ce,0), payroll_luong_thuc_nhan, payroll_nghia_vu_gv, payroll_phu_cap_xang_xe, payroll_nld_nop_bhxh_10_5, payroll_nguoi_phu_thuoc FROM table_product WHERE type='nhan-vien' AND payroll_department='giao_vien' AND stt > 0 AND JSON_UNQUOTE(JSON_EXTRACT(options2, '$.import_meta.file_name'))='LUONG.xlsm' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(options2, '$.import_meta.imported_at')) AS UNSIGNED) >= $RUN_TS ORDER BY stt ASC, id ASC LIMIT 3;" | LC_ALL=C tr -d '\r')"

if [[ -z "$SAMPLE_ROWS" ]]; then
	fail "no imported teacher rows found for formula validation"
fi

SAMPLE_COUNT=0
while IFS=$'\t' read -r EMPLOYEE_NAME LOOKUP_CODE TD_COUNT SS_COUNT C1_COUNT CE_COUNT LUONG_THUC_NHAN NGHIA_VU_GV PHU_CAP_XANG_XE NLD_NOP_BHXH NGUOI_PHU_THUOC; do
	[[ -z "$LOOKUP_CODE" ]] && continue
	SAMPLE_COUNT=$((SAMPLE_COUNT + 1))
	EXPECTED_NHAN_RAW=$(( $(money_to_int "$LUONG_THUC_NHAN") - $(money_to_int "$NGHIA_VU_GV") ))
	if (( EXPECTED_NHAN_RAW > 0 )); then EXPECT_NHAN="$(format_vn_int "$EXPECTED_NHAN_RAW")"; else EXPECT_NHAN='-'; fi
	EXPECT_LUONG_CE="$(format_vn_int $(( CE_COUNT * RATE_CE )))"
	EXPECT_DS_PHAN_XE="$(format_vn_int $(( TD_COUNT * RATE_TD + SS_COUNT * RATE_SS + C1_COUNT * RATE_C1 )))"
	EXPECT_BTD="${TD_COUNT:-0}"
	EXPECT_BSS="${SS_COUNT:-0}"
	EXPECT_C1="${C1_COUNT:-0}"
	[[ "$EXPECT_BTD" == "0" ]] && EXPECT_BTD='-'
	[[ "$EXPECT_BSS" == "0" ]] && EXPECT_BSS='-'
	[[ "$EXPECT_C1" == "0" ]] && EXPECT_C1='-'
	EXPECT_PHU_CAP="$(format_vn_money "$PHU_CAP_XANG_XE")"
	EXPECT_BHXH="$(format_vn_money "$NLD_NOP_BHXH")"

	LOOKUP_HTML="$(curl -s -X POST 'http://localhost:8080/ajax/tracuu_nhanvien.php' --data-urlencode 'action=lookup' --data-urlencode "keyword=$LOOKUP_CODE")"
	LOOKUP_HTML_B64="$(printf '%s' "$LOOKUP_HTML" | base64 | LC_ALL=C tr -d '\n')"

	docker compose exec -T \
		-e LOOKUP_HTML_B64="$LOOKUP_HTML_B64" \
		-e EXPECT_NHAN="$EXPECT_NHAN" \
		-e EXPECT_LUONG_CE="$EXPECT_LUONG_CE" \
		-e EXPECT_DS_PHAN_XE="$EXPECT_DS_PHAN_XE" \
		-e EXPECT_BTD="$EXPECT_BTD" \
		-e EXPECT_BSS="$EXPECT_BSS" \
		-e EXPECT_C1="$EXPECT_C1" \
		-e EXPECT_PHU_CAP="$EXPECT_PHU_CAP" \
		-e EXPECT_BHXH="$EXPECT_BHXH" \
		-e LOOKUP_CODE="$LOOKUP_CODE" \
		-e EMPLOYEE_NAME="$EMPLOYEE_NAME" \
		"$WEB_SERVICE" php <<'PHP' >/dev/null
<?php
$html = base64_decode(getenv('LOOKUP_HTML_B64'));

function extractValue($html, $label)
{
	$pattern = '~>\s*' . preg_quote($label, '~') . '\s*</div>\s*<div[^>]*>\s*(.*?)\s*</div>~u';
	if (!preg_match($pattern, $html, $matches)) return null;
	return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8'));
}

$checks = array(
	'Nhận' => getenv('EXPECT_NHAN'),
	'Lương CE' => getenv('EXPECT_LUONG_CE'),
	'L theo DS phân xe' => getenv('EXPECT_DS_PHAN_XE'),
	'B(TĐ)' => getenv('EXPECT_BTD'),
	'B(SS)' => getenv('EXPECT_BSS'),
	'C1' => getenv('EXPECT_C1'),
	'Phụ cấp thêm' => getenv('EXPECT_PHU_CAP'),
	'BHXH' => getenv('EXPECT_BHXH'),
);

foreach ($checks as $label => $expected) {
	$actual = extractValue($html, $label);
	if ($actual === null) {
		fwrite(STDERR, "Missing label [$label] for " . getenv('EMPLOYEE_NAME') . " (" . getenv('LOOKUP_CODE') . ")\n");
		exit(1);
	}
	if ($actual !== $expected) {
		fwrite(STDERR, "Label [$label] mismatch for " . getenv('EMPLOYEE_NAME') . " (" . getenv('LOOKUP_CODE') . "): expected [$expected], got [$actual]\n");
		exit(1);
	}
}
PHP
	echo "validated lookup $LOOKUP_CODE for $EMPLOYEE_NAME"
done <<<"$SAMPLE_ROWS"

if [[ "$SAMPLE_COUNT" -lt 3 ]]; then
	fail "expected at least 3 imported teacher rows for lookup validation"
fi

echo "[6/6] PASS"
echo "[PASS] Payroll regression import + public lookup"