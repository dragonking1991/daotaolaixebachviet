<?php
/*
 * Xác thực cổng giáo viên: bcrypt (password_hash), tự nâng cấp mật khẩu MD5 cũ khi đăng nhập đúng,
 * giới hạn số lần thử đăng nhập/đặt lại mật khẩu và token CSRF cho các form trong cổng.
 */

if(!function_exists('dt_gv_hash'))
{
	function dt_gv_hash($plain)
	{
		return password_hash((string)$plain, PASSWORD_DEFAULT);
	}
}

if(!function_exists('dt_gv_legacy_hash'))
{
	function dt_gv_legacy_hash($plain)
	{
		return md5('dt_gv_'.$plain.'_bachviet');
	}

	function dt_gv_verify($plain, $stored)
	{
		$stored = (string)$stored;
		if($stored === '') return false;
		$info = password_get_info($stored);
		if(!empty($info['algo'])) return password_verify((string)$plain, $stored);
		return hash_equals($stored, dt_gv_legacy_hash($plain));
	}

	function dt_gv_needs_rehash($stored)
	{
		$info = password_get_info((string)$stored);
		return empty($info['algo']) || password_needs_rehash((string)$stored, PASSWORD_DEFAULT);
	}

	/* Mật khẩu vẫn là mặc định (bằng CCCD) thì bắt buộc đổi trước khi dùng cổng. */
	function dt_gv_is_default_password($cccd, $stored)
	{
		$cccd = (string)$cccd;
		if($cccd === '' || (string)$stored === '') return false;
		foreach(array_unique(array($cccd, ltrim($cccd, '0'), '0'.$cccd)) as $candidate)
			if($candidate !== '' && dt_gv_verify($candidate, $stored)) return true;
		return false;
	}

	function dt_gv_password_policy($new, $cccd = '')
	{
		$new = (string)$new;
		if(strlen($new) < 8) return 'Mật khẩu mới tối thiểu 8 ký tự.';
		$digits = preg_replace('/\D+/', '', (string)$cccd);
		if($digits !== '' && preg_replace('/\D+/', '', $new) === $digits && ctype_digit($new)) return 'Không dùng CCCD làm mật khẩu.';
		return '';
	}

	function dt_gv_attempt_table()
	{
		global $d;
		static $ready = false;
		if($ready) return;
		$d->rawQuery("create table if not exists #_dt_gv_attempt (
			akey char(64) not null primary key,
			fails int not null default 0,
			first_at int not null default 0,
			locked_until int not null default 0
		) engine=InnoDB default charset=utf8mb4");
		$ready = true;
	}

	function dt_gv_attempt_keys($scope, $cccd)
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
		return array(
			hash('sha256', $scope.'|id|'.preg_replace('/\D+/', '', (string)$cccd)) => 5,
			hash('sha256', $scope.'|ip|'.$ip) => 20,
		);
	}

	/* Trả về số phút còn bị khóa (0 nếu được phép thử). */
	function dt_gv_attempt_locked($scope, $cccd)
	{
		global $d;
		dt_gv_attempt_table();
		$now = time();
		foreach(dt_gv_attempt_keys($scope, $cccd) as $key => $limit)
		{
			$row = $d->rawQueryOne("select locked_until from #_dt_gv_attempt where akey = ? limit 0,1", array($key));
			if($row && (int)$row['locked_until'] > $now) return (int)ceil(((int)$row['locked_until'] - $now) / 60);
		}
		return 0;
	}

	function dt_gv_attempt_fail($scope, $cccd)
	{
		global $d;
		dt_gv_attempt_table();
		$now = time(); $window = 900;
		foreach(dt_gv_attempt_keys($scope, $cccd) as $key => $limit)
		{
			$row = $d->rawQueryOne("select fails, first_at from #_dt_gv_attempt where akey = ? limit 0,1", array($key));
			$fails = ($row && (int)$row['first_at'] > $now - $window) ? (int)$row['fails'] + 1 : 1;
			$first = ($row && (int)$row['first_at'] > $now - $window) ? (int)$row['first_at'] : $now;
			$locked = $fails >= $limit ? $now + $window : 0;
			$d->rawQuery("replace into #_dt_gv_attempt (akey, fails, first_at, locked_until) values (?, ?, ?, ?)", array($key, $fails, $first, $locked));
		}
	}

	function dt_gv_attempt_clear($scope, $cccd)
	{
		global $d;
		dt_gv_attempt_table();
		$keys = array_keys(dt_gv_attempt_keys($scope, $cccd));
		$d->rawQuery("delete from #_dt_gv_attempt where akey = ?", array($keys[0]));
	}

	function dt_gv_csrf_token()
	{
		if(empty($_SESSION['dt_gv_csrf'])) $_SESSION['dt_gv_csrf'] = bin2hex(random_bytes(32));
		return $_SESSION['dt_gv_csrf'];
	}

	function dt_gv_csrf_valid()
	{
		return !empty($_SESSION['dt_gv_csrf']) && hash_equals((string)$_SESSION['dt_gv_csrf'], (string)($_POST['dt_csrf'] ?? ''));
	}
}
