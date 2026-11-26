<?php
/* Lịch ôn sát hạch (đăng ký giờ ôn xe thiết bị) — dùng chung cho admin và cổng giáo viên. Cần global $d.
 * Quy trình: Đợt ôn (ngày SH + sân) → cấu hình ngày tập/hạng/xe/khung giờ → GV đăng ký số giờ theo hạng
 * → xếp lịch (tự động + chỉnh tay) → công bố cho GV và xuất Excel. Mỗi ô lịch = 1 giờ trên 1 xe. */

if(!function_exists('lo_ensure_tables'))
{
	function lo_hang_list()
	{
		return array('B số sàn', 'B tự động', 'C1', 'C', 'D', 'CE');
	}

	/* Khung giờ mặc định khi thêm ngày tập (theo mẫu lịch sân: hạng B 7h–19h, hạng khác 6h–20h). */
	function lo_hang_default_hours($hang)
	{
		return strpos((string)$hang, 'B') === 0 ? array(7, 19) : array(6, 20);
	}

	function lo_status_list()
	{
		return array(
			'nhap' => array('label' => 'Đang chuẩn bị', 'badge' => 'secondary', 'icon' => 'fa-pencil-ruler', 'hint' => 'Cấu hình ngày tập và xe. Giáo viên chưa nhìn thấy đợt này.'),
			'mo_dk' => array('label' => 'Đang mở đăng ký', 'badge' => 'success', 'icon' => 'fa-door-open', 'hint' => 'Giáo viên đăng nhập Cổng GV để đăng ký / sửa số giờ ôn.'),
			'khoa_dk' => array('label' => 'Đã khóa đăng ký', 'badge' => 'warning', 'icon' => 'fa-lock', 'hint' => 'Giáo viên không sửa được đăng ký. Bộ phận tiến hành xếp lịch.'),
			'cong_bo' => array('label' => 'Đã công bố lịch', 'badge' => 'primary', 'icon' => 'fa-bullhorn', 'hint' => 'Giáo viên xem được lịch đã xếp của mình trên Cổng GV.'),
		);
	}

	/* Tổng giờ GV đăng ký trong 1 đợt vượt ngưỡng này thì phải chọn cách xử lý (lo_xac_nhan_list). */
	function lo_gio_canh_bao()
	{
		return 12;
	}

	function lo_xac_nhan_list()
	{
		return array(
			'song_song' => array('label' => 'Tập song song 2 xe', 'short' => 'Song song 2 xe', 'icon' => 'fa-random'),
			'hai_ngay' => array('label' => 'Tập trong 2 ngày', 'short' => 'Tập 2 ngày', 'icon' => 'fa-calendar-week'),
			'phu_trach' => array('label' => 'Gửi người phụ trách xếp', 'short' => 'Nhờ phụ trách xếp', 'icon' => 'fa-user-tie'),
		);
	}

	/* Bổ sung cột cho bảng đã tạo ở phiên bản trước. Trả về true nếu vừa thêm cột xac_nhan (lần nâng cấp đầu tiên). */
	function lo_upgrade_columns()
	{
		global $d;
		$have = array();
		foreach($d->rawQuery('show columns from #_dt_lo_dangky') as $r) $have[strtolower($r['Field'])] = true;
		$added = false;
		if(!isset($have['gv_sdt'])) $d->rawQuery("alter table #_dt_lo_dangky add column gv_sdt VARCHAR(20) NOT NULL DEFAULT '' after gv_cccd");
		if(!isset($have['xac_nhan'])) { $d->rawQuery("alter table #_dt_lo_dangky add column xac_nhan VARCHAR(20) NOT NULL DEFAULT '' after so_gio"); $added = true; }
		return $added;
	}

	/* Đổi tên lịch tự động kiểu cũ (1 chữ, VD "Minh" / "Minh V") sang tên 2 chữ. Tên do bộ phận tự đặt được giữ nguyên. */
	function lo_upgrade_ten_lich()
	{
		global $d;
		$rows = $d->rawQuery('select id, id_dot, id_gv, gv_hoten, ten_lich from #_dt_lo_dangky order by id_dot asc, id asc');
		$byDot = array();
		foreach($rows as $r) $byDot[$r['id_dot']][$r['id_gv']] = $r;
		foreach($byDot as $idDot => $gvs)
		{
			$taken = array();
			$todo = array();
			foreach($gvs as $idGv => $r)
			{
				$words = preg_split('/\s+/u', trim((string)$r['gv_hoten']), -1, PREG_SPLIT_NO_EMPTY);
				$last = $words ? $words[count($words) - 1] : '';
				$isOld = $last !== '' && count($words) >= 2 && preg_match('/^'.preg_quote($last, '/').'( \p{Lu}| \d+)?$/u', (string)$r['ten_lich']);
				if($isOld) $todo[$idGv] = $r; else $taken[] = $r['ten_lich'];
			}
			foreach($todo as $idGv => $r)
			{
				$name = lo_short_name($r['gv_hoten'], $taken);
				$base = $name; $i = 2;
				while(in_array($name, $taken, true)) $name = $base.' '.$i++;
				$taken[] = $name;
				$d->rawQuery('update #_dt_lo_dangky set ten_lich = ? where id_dot = ? and id_gv = ?', array(mb_substr($name, 0, 50), (int)$idDot, (int)$idGv));
			}
		}
	}

	function lo_ensure_tables()
	{
		global $d;
		static $ready = false;
		if($ready) return;
		$sqls = array(
			"CREATE TABLE IF NOT EXISTS #_dt_lo_dot (
				id INT AUTO_INCREMENT PRIMARY KEY,
				ngay_sh DATE NOT NULL,
				san VARCHAR(100) NOT NULL DEFAULT '',
				id_sh_ky INT NOT NULL DEFAULT 0,
				trang_thai VARCHAR(20) NOT NULL DEFAULT 'nhap',
				max_song_song TINYINT NOT NULL DEFAULT 3,
				ghi_chu VARCHAR(1000) NOT NULL DEFAULT '',
				nguoi_tao VARCHAR(255) NOT NULL DEFAULT '',
				ngaytao INT NOT NULL DEFAULT 0,
				ngaycapnhat INT NOT NULL DEFAULT 0,
				KEY idx_lo_dot_ngay (ngay_sh)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS #_dt_lo_ngay (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_dot INT NOT NULL,
				ngay DATE NOT NULL,
				hang VARCHAR(30) NOT NULL,
				gio_bd TINYINT NOT NULL,
				gio_kt TINYINT NOT NULL,
				xe_list VARCHAR(1000) NOT NULL DEFAULT '',
				UNIQUE KEY uq_lo_ngay (id_dot, ngay, hang)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS #_dt_lo_dangky (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_dot INT NOT NULL,
				id_gv INT NOT NULL,
				gv_hoten VARCHAR(255) NOT NULL DEFAULT '',
				gv_cccd VARCHAR(20) NOT NULL DEFAULT '',
				gv_sdt VARCHAR(20) NOT NULL DEFAULT '',
				ten_lich VARCHAR(50) NOT NULL DEFAULT '',
				hang VARCHAR(30) NOT NULL,
				so_gio SMALLINT NOT NULL DEFAULT 0,
				xac_nhan VARCHAR(20) NOT NULL DEFAULT '',
				ghi_chu VARCHAR(1000) NOT NULL DEFAULT '',
				uu_tien_ngay DATE NULL DEFAULT NULL,
				uu_tien_gio_bd TINYINT NULL DEFAULT NULL,
				uu_tien_gio_kt TINYINT NULL DEFAULT NULL,
				admin_ghi_chu VARCHAR(500) NOT NULL DEFAULT '',
				nguon VARCHAR(10) NOT NULL DEFAULT 'gv',
				ngaytao INT NOT NULL DEFAULT 0,
				ngaycapnhat INT NOT NULL DEFAULT 0,
				UNIQUE KEY uq_lo_dk (id_dot, id_gv, hang),
				KEY idx_lo_dk_gv (id_gv)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
			"CREATE TABLE IF NOT EXISTS #_dt_lo_lich (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_dot INT NOT NULL,
				id_ngay INT NOT NULL,
				so_xe VARCHAR(20) NOT NULL,
				gio TINYINT NOT NULL,
				id_dangky INT NOT NULL,
				nguon VARCHAR(10) NOT NULL DEFAULT 'auto',
				ngaytao INT NOT NULL DEFAULT 0,
				UNIQUE KEY uq_lo_lich_o (id_ngay, so_xe, gio),
				KEY idx_lo_lich_dk (id_dangky),
				KEY idx_lo_lich_dot (id_dot)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
		);
		foreach($sqls as $sql)
		{
			$d->rawQuery($sql);
			if(method_exists($d, 'getLastErrorCode') && $d->getLastErrorCode() !== '00000')
				throw new RuntimeException('Không thể tạo bảng lịch ôn sát hạch.');
		}
		if(lo_upgrade_columns()) lo_upgrade_ten_lich();
		$ready = true;
	}

	/* Thêm đăng ký bằng rawQuery: PDODb::insert tự điền cột NULL bằng 0 / 0000-00-00 (lỗi khi MySQL bật strict mode). */
	function lo_insert_dangky($data)
	{
		global $d;
		$cols = array('id_dot', 'id_gv', 'gv_hoten', 'gv_cccd', 'gv_sdt', 'ten_lich', 'hang', 'so_gio', 'xac_nhan', 'ghi_chu', 'uu_tien_ngay', 'uu_tien_gio_bd', 'uu_tien_gio_kt', 'admin_ghi_chu', 'nguon', 'ngaytao', 'ngaycapnhat');
		$defaults = array('gv_hoten' => '', 'gv_cccd' => '', 'gv_sdt' => '', 'ten_lich' => '', 'xac_nhan' => '', 'ghi_chu' => '', 'uu_tien_ngay' => null, 'uu_tien_gio_bd' => null, 'uu_tien_gio_kt' => null, 'admin_ghi_chu' => '', 'nguon' => 'gv', 'ngaytao' => time(), 'ngaycapnhat' => time());
		$params = array();
		foreach($cols as $c) $params[] = array_key_exists($c, $data) ? $data[$c] : ($defaults[$c] ?? null);
		$d->rawQuery('insert into #_dt_lo_dangky ('.implode(', ', $cols).') values ('.implode(', ', array_fill(0, count($cols), '?')).')', $params);
		if(method_exists($d, 'getLastErrorCode') && $d->getLastErrorCode() !== '00000') return false;
		$row = $d->rawQueryOne('select last_insert_id() as id');
		return $row ? (int)$row['id'] : false;
	}

	/* Chuẩn hóa ưu tiên đọc từ DB: ngày 0000-00-00 → không yêu cầu; giờ kết thúc 0/rỗng → 24; khung giờ rỗng → không yêu cầu. */
	function lo_norm_prefs($r)
	{
		$ngay = isset($r['uu_tien_ngay']) ? (string)$r['uu_tien_ngay'] : '';
		$r['uu_tien_ngay'] = ($ngay === '' || strpos($ngay, '0000') === 0) ? null : $ngay;
		$bd = isset($r['uu_tien_gio_bd']) && $r['uu_tien_gio_bd'] !== '' ? (int)$r['uu_tien_gio_bd'] : null;
		$kt = isset($r['uu_tien_gio_kt']) && $r['uu_tien_gio_kt'] !== '' && (int)$r['uu_tien_gio_kt'] > 0 ? (int)$r['uu_tien_gio_kt'] : null;
		if($bd !== null && $kt === null) $kt = 24;
		if($bd === null && $kt !== null) $bd = 0;
		if($bd !== null && ($kt <= $bd || ($bd === 0 && $kt === 24))) { $bd = null; $kt = null; }
		$r['uu_tien_gio_bd'] = $bd;
		$r['uu_tien_gio_kt'] = $kt;
		return $r;
	}

	/* "21, 23-25, 89" → ['21','23','24','25','89']. Trả về false nếu có phần không hợp lệ. */
	function lo_parse_xe_list($raw, &$error = '')
	{
		$error = '';
		$out = array();
		$parts = preg_split('/[\s,;]+/u', trim((string)$raw), -1, PREG_SPLIT_NO_EMPTY);
		foreach($parts as $part)
		{
			if(preg_match('/^(\d{1,6})-(\d{1,6})$/', $part, $m))
			{
				$a = (int)$m[1]; $b = (int)$m[2];
				if($b < $a || $b - $a > 100) { $error = 'Khoảng số xe "'.$part.'" không hợp lệ.'; return false; }
				for($i = $a; $i <= $b; $i++) $out[] = (string)$i;
			}
			elseif(preg_match('/^[0-9A-Za-z.\-]{1,20}$/', $part)) $out[] = $part;
			else { $error = 'Số xe "'.$part.'" không hợp lệ.'; return false; }
		}
		$out = array_values(array_unique($out));
		if(count($out) > 100) { $error = 'Tối đa 100 xe cho một hạng trong một ngày.'; return false; }
		return $out;
	}

	function lo_xe_array($xeList)
	{
		$xe = lo_parse_xe_list($xeList);
		return $xe ? $xe : array();
	}

	function lo_hour_label($h)
	{
		return (int)$h.'h00';
	}

	function lo_range_label($bd, $kt)
	{
		return sprintf('%02d:00–%02d:00', (int)$bd, (int)$kt);
	}

	function lo_weekday($date)
	{
		$names = array('Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy');
		$ts = strtotime((string)$date);
		return $ts ? $names[(int)date('w', $ts)] : '';
	}

	function lo_date_vn($date)
	{
		$ts = strtotime((string)$date);
		return $ts ? date('d/m/Y', $ts) : '';
	}

	function lo_dot_title($dot)
	{
		$t = 'Sát hạch '.lo_date_vn($dot['ngay_sh']);
		if(trim((string)$dot['san']) !== '') $t .= ' – '.trim((string)$dot['san']);
		return $t;
	}

	/* Tên trên lưới lịch: 2 chữ cuối của họ tên (VD "Duy Minh") để tránh trùng tên gọi;
	 * vẫn trùng thì thêm chữ cái đầu của chữ liền trước (VD "Duy Minh V"). */
	function lo_short_name($hoten, $taken = array())
	{
		$words = preg_split('/\s+/u', trim((string)$hoten), -1, PREG_SPLIT_NO_EMPTY);
		if(!$words) return '';
		$n = count($words);
		$name = $n >= 2 ? $words[$n - 2].' '.$words[$n - 1] : $words[0];
		if(!in_array($name, $taken, true) || $n < 3) return $name;
		return $name.' '.mb_strtoupper(mb_substr($words[$n - 3], 0, 1));
	}

	/* Tổng giờ một GV đã đăng ký trong đợt (trừ hạng $exceptHang). */
	function lo_gv_total_hours($idDot, $idGv, $exceptHang = '')
	{
		global $d;
		$row = $d->rawQueryOne('select coalesce(sum(so_gio), 0) as n from #_dt_lo_dangky where id_dot = ? and id_gv = ? and hang <> ?', array((int)$idDot, (int)$idGv, (string)$exceptHang));
		return $row ? (int)$row['n'] : 0;
	}

	/* Cách xử lý khi vượt ngưỡng giờ áp dụng cho mọi đăng ký của GV trong đợt; dưới ngưỡng thì bỏ trống. */
	function lo_set_xac_nhan($idDot, $idGv, $xacNhan)
	{
		global $d;
		$total = lo_gv_total_hours($idDot, $idGv);
		if($total <= lo_gio_canh_bao()) $xacNhan = '';
		$d->rawQuery('update #_dt_lo_dangky set xac_nhan = ? where id_dot = ? and id_gv = ?', array((string)$xacNhan, (int)$idDot, (int)$idGv));
	}

	/* GV ngoài hệ thống dùng id_gv âm, duy nhất trong đợt; cùng họ tên (+ CCCD nếu có) thì dùng lại id cũ để gộp các hạng. */
	function lo_external_gv_id($idDot, $hoten, $cccd)
	{
		global $d;
		foreach($d->rawQuery('select distinct id_gv, gv_hoten, gv_cccd from #_dt_lo_dangky where id_dot = ? and id_gv < 0', array((int)$idDot)) as $r)
			if(mb_strtolower(trim($r['gv_hoten'])) === mb_strtolower(trim($hoten)) && (string)$r['gv_cccd'] === (string)$cccd) return (int)$r['id_gv'];
		$row = $d->rawQueryOne('select least(coalesce(min(id_gv), 0), 0) as m from #_dt_lo_dangky where id_dot = ?', array((int)$idDot));
		return (int)($row['m'] ?? 0) - 1;
	}

	/* Màu ổn định theo đăng ký để dễ nhìn thấy tính liên tục trên lưới. */
	function lo_color($seed)
	{
		$h = (crc32('lo'.(string)$seed) % 360 + 360) % 360;
		return 'hsl('.$h.', 70%, 88%)';
	}

	function lo_dot_get($id)
	{
		global $d;
		return $d->rawQueryOne('select * from #_dt_lo_dot where id = ? limit 0,1', array((int)$id));
	}

	function lo_ngay_rows($idDot)
	{
		global $d;
		$rows = $d->rawQuery('select * from #_dt_lo_ngay where id_dot = ? order by ngay asc, id asc', array((int)$idDot));
		$order = array_flip(lo_hang_list());
		usort($rows, function($a, $b) use ($order) {
			if($a['ngay'] !== $b['ngay']) return strcmp($a['ngay'], $b['ngay']);
			$oa = isset($order[$a['hang']]) ? $order[$a['hang']] : 99;
			$ob = isset($order[$b['hang']]) ? $order[$b['hang']] : 99;
			return $oa - $ob;
		});
		foreach($rows as &$r)
		{
			$r['xe'] = lo_xe_array($r['xe_list']);
			$r['so_gio_xe'] = max(0, (int)$r['gio_kt'] - (int)$r['gio_bd']);
			$r['tong_gio'] = count($r['xe']) * $r['so_gio_xe'];
		}
		unset($r);
		return $rows;
	}

	/* Tổng hợp theo hạng: giờ sân, đã đăng ký, đã xếp. */
	function lo_capacity($idDot)
	{
		global $d;
		$cap = array();
		foreach(lo_ngay_rows($idDot) as $r)
		{
			if(!isset($cap[$r['hang']])) $cap[$r['hang']] = array('tong_gio' => 0, 'da_dk' => 0, 'da_xep' => 0, 'so_ngay' => 0);
			$cap[$r['hang']]['tong_gio'] += $r['tong_gio'];
			$cap[$r['hang']]['so_ngay']++;
		}
		$rows = $d->rawQuery('select hang, sum(so_gio) as dk from #_dt_lo_dangky where id_dot = ? group by hang', array((int)$idDot));
		foreach($rows as $r)
		{
			if(!isset($cap[$r['hang']])) $cap[$r['hang']] = array('tong_gio' => 0, 'da_dk' => 0, 'da_xep' => 0, 'so_ngay' => 0);
			$cap[$r['hang']]['da_dk'] = (int)$r['dk'];
		}
		$rows = $d->rawQuery('select n.hang, count(*) as so from #_dt_lo_lich l join #_dt_lo_ngay n on n.id = l.id_ngay where l.id_dot = ? group by n.hang', array((int)$idDot));
		foreach($rows as $r) if(isset($cap[$r['hang']])) $cap[$r['hang']]['da_xep'] = (int)$r['so'];
		$order = array_flip(lo_hang_list());
		uksort($cap, function($a, $b) use ($order) {
			return (isset($order[$a]) ? $order[$a] : 99) - (isset($order[$b]) ? $order[$b] : 99);
		});
		return $cap;
	}

	/* Đăng ký kèm số giờ đã xếp. */
	function lo_dangky_rows($idDot, $idGv = 0)
	{
		global $d;
		$sql = 'select dk.*, (select count(*) from #_dt_lo_lich l where l.id_dangky = dk.id) as da_xep from #_dt_lo_dangky dk where dk.id_dot = ?';
		$params = array((int)$idDot);
		if($idGv) { $sql .= ' and dk.id_gv = ?'; $params[] = (int)$idGv; }
		$rows = $d->rawQuery($sql, $params);
		$order = array_flip(lo_hang_list());
		usort($rows, function($a, $b) use ($order) {
			$oa = isset($order[$a['hang']]) ? $order[$a['hang']] : 99;
			$ob = isset($order[$b['hang']]) ? $order[$b['hang']] : 99;
			if($oa !== $ob) return $oa - $ob;
			if((int)$a['so_gio'] !== (int)$b['so_gio']) return (int)$b['so_gio'] - (int)$a['so_gio'];
			return strcmp($a['ten_lich'], $b['ten_lich']);
		});
		foreach($rows as &$r)
		{
			$r = lo_norm_prefs($r);
			$r['da_xep'] = (int)$r['da_xep'];
			$r['con_lai'] = (int)$r['so_gio'] - $r['da_xep'];
		}
		unset($r);
		return $rows;
	}

	/* Tên lịch chưa dùng trong đợt cho GV (giữ tên cũ nếu GV đã có đăng ký khác trong đợt). */
	function lo_pick_ten_lich($idDot, $idGv, $hoten)
	{
		global $d;
		$own = $d->rawQueryOne('select ten_lich from #_dt_lo_dangky where id_dot = ? and id_gv = ? and ten_lich <> \'\' limit 0,1', array((int)$idDot, (int)$idGv));
		if($own) return $own['ten_lich'];
		$taken = array();
		foreach($d->rawQuery('select distinct ten_lich from #_dt_lo_dangky where id_dot = ? and id_gv <> ?', array((int)$idDot, (int)$idGv)) as $r) $taken[] = $r['ten_lich'];
		$name = lo_short_name($hoten, $taken);
		$base = $name; $i = 2;
		while(in_array($name, $taken, true)) $name = $base.' '.$i++;
		return mb_substr($name, 0, 50);
	}

	/* Gộp các ô lịch liên tiếp (cùng ngày, hạng, xe) thành khối giờ để hiển thị cho GV. */
	function lo_gv_blocks($slots)
	{
		usort($slots, function($a, $b) {
			if($a['ngay'] !== $b['ngay']) return strcmp($a['ngay'], $b['ngay']);
			if((int)$a['gio'] !== (int)$b['gio']) return (int)$a['gio'] - (int)$b['gio'];
			return strcmp($a['so_xe'], $b['so_xe']);
		});
		$days = array();
		foreach($slots as $s)
		{
			$day = &$days[$s['ngay']];
			if($day === null) $day = array();
			$merged = false;
			foreach($day as &$b)
				if($b['hang'] === $s['hang'] && $b['so_xe'] === $s['so_xe'] && $b['gio_kt'] === (int)$s['gio']) { $b['gio_kt']++; $merged = true; break; }
			unset($b);
			if(!$merged) $day[] = array('hang' => $s['hang'], 'so_xe' => $s['so_xe'], 'gio_bd' => (int)$s['gio'], 'gio_kt' => (int)$s['gio'] + 1);
			unset($day);
		}
		foreach($days as &$blocks) usort($blocks, function($a, $b) { return $a['gio_bd'] - $b['gio_bd']; });
		unset($blocks);
		ksort($days);
		return $days;
	}

	/* ===== Xếp lịch tự động (thuần, không truy cập DB) =====
	 * $configs: [id, ngay, hang, gio_bd, gio_kt, xe[]]
	 * $regs:    [id, id_gv, hang, so_gio, uu_tien_ngay, uu_tien_gio_bd, uu_tien_gio_kt, ten_lich]
	 * $slots:   ô đã có (giữ nguyên) [id_ngay, so_xe, gio, id_dangky]
	 * Quy tắc:
	 *  1. GV có yêu cầu (ngày/khung giờ) xếp trước, sau đó theo tổng số giờ đăng ký giảm dần.
	 *  2. Trong 1 ngày, giờ của 1 GV xếp liên tục (bất kể hạng), đổi sang xe khác cùng hạng khi xe đang dùng bận.
	 *  3. Không xếp liên tục đủ thì xếp song song 2 xe cùng giờ, tối đa $maxParallel giờ/ngày, để tránh đẩy sang ngày sau
 *     (trừ GV đã chọn "tập trong 2 ngày" – xac_nhan = hai_ngay – thì không xếp song song).
	 *  4. Còn thiếu thì chuyển sang ngày tiếp theo; cuối cùng lấp vào chỗ trống còn lại.
	 * Trả về ['new' => [id_ngay, so_xe, gio, id_dangky][], 'unplaced' => [id_dangky => giờ]]. */
	function lo_schedule($configs, $regs, $slots, $maxParallel = 3)
	{
		$hangOrder = array_flip(lo_hang_list());
		$cfg = array();      // [ngay][hang] => config
		$cfgById = array();
		foreach($configs as $c)
		{
			$c['gio_bd'] = (int)$c['gio_bd']; $c['gio_kt'] = (int)$c['gio_kt'];
			$cfg[$c['ngay']][$c['hang']] = $c;
			$cfgById[$c['id']] = $c;
		}
		$days = array_keys($cfg);
		sort($days);

		$regs = array_map('lo_norm_prefs', $regs);
		$regById = array();
		foreach($regs as $r) $regById[$r['id']] = $r;
		$occ = array();      // [id_ngay][xe][gio] => id_dangky
		$busy = array();     // [id_gv][ngay][gio] => số ô
		$par = array();      // [id_gv][ngay] => số giờ song song
		$remain = array();
		$new = array();
		foreach($regs as $r) $remain[$r['id']] = (int)$r['so_gio'];
		foreach($slots as $s)
		{
			$occ[$s['id_ngay']][$s['so_xe']][(int)$s['gio']] = $s['id_dangky'];
			if(!isset($regById[$s['id_dangky']]) || !isset($cfgById[$s['id_ngay']])) continue;
			$gv = $regById[$s['id_dangky']]['id_gv'];
			$day = $cfgById[$s['id_ngay']]['ngay'];
			$busy[$gv][$day][(int)$s['gio']] = (isset($busy[$gv][$day][(int)$s['gio']]) ? $busy[$gv][$day][(int)$s['gio']] : 0) + 1;
			if($busy[$gv][$day][(int)$s['gio']] > 1) $par[$gv][$day] = (isset($par[$gv][$day]) ? $par[$gv][$day] : 0) + 1;
			$remain[$s['id_dangky']]--;
		}

		$byGv = array();
		foreach($regs as $r) $byGv[$r['id_gv']][] = $r;
		$gvOrder = array_keys($byGv);
		usort($gvOrder, function($a, $b) use ($byGv, $remain) {
			$pa = 0; $pb = 0; $ta = 0; $tb = 0;
			foreach($byGv[$a] as $r) { $pa |= ($r['uu_tien_ngay'] || $r['uu_tien_gio_bd'] !== null) ? 1 : 0; $ta += max(0, $remain[$r['id']]); }
			foreach($byGv[$b] as $r) { $pb |= ($r['uu_tien_ngay'] || $r['uu_tien_gio_bd'] !== null) ? 1 : 0; $tb += max(0, $remain[$r['id']]); }
			if($pa !== $pb) return $pb - $pa;
			if($ta !== $tb) return $tb - $ta;
			return $a - $b;
		});

		$inWindow = function($r, $t) {
			if($r['uu_tien_gio_bd'] === null || $r['uu_tien_gio_bd'] === '') return true;
			$bd = (int)$r['uu_tien_gio_bd'];
			$kt = ($r['uu_tien_gio_kt'] === null || $r['uu_tien_gio_kt'] === '') ? 24 : (int)$r['uu_tien_gio_kt'];
			return $t >= $bd && $t < $kt;
		};
		$xeFree = function($c, $xe, $t) use (&$occ) {
			return $t >= $c['gio_bd'] && $t < $c['gio_kt'] && !isset($occ[$c['id']][$xe][$t]);
		};
		$freeRun = function($c, $xe, $t) use ($xeFree) {
			$n = 0;
			while($xeFree($c, $xe, $t + $n)) $n++;
			return $n;
		};
		$pickXe = function($c, $t, $prevXe) use ($xeFree, $freeRun) {
			if($prevXe !== null && in_array($prevXe, $c['xe'], true) && $xeFree($c, $prevXe, $t)) return $prevXe;
			$best = null; $bestRun = 0;
			foreach($c['xe'] as $xe)
			{
				$run = $freeRun($c, $xe, $t);
				if($run > $bestRun) { $best = $xe; $bestRun = $run; }
			}
			return $best;
		};
		$place = function($c, $xe, $t, $r) use (&$occ, &$busy, &$par, &$remain, &$new) {
			$occ[$c['id']][$xe][$t] = $r['id'];
			$cnt = isset($busy[$r['id_gv']][$c['ngay']][$t]) ? $busy[$r['id_gv']][$c['ngay']][$t] : 0;
			$busy[$r['id_gv']][$c['ngay']][$t] = $cnt + 1;
			if($cnt > 0) $par[$r['id_gv']][$c['ngay']] = (isset($par[$r['id_gv']][$c['ngay']]) ? $par[$r['id_gv']][$c['ngay']] : 0) + 1;
			$remain[$r['id']]--;
			$new[] = array('id_ngay' => $c['id'], 'so_xe' => $xe, 'gio' => $t, 'id_dangky' => $r['id']);
		};

		/* Mô phỏng 1 chuỗi giờ liên tục bắt đầu từ $start; trả về danh sách ô dự kiến. */
		$simulate = function($gvRegs, $gv, $day, $start) use (&$cfg, &$busy, &$remain, $inWindow, $xeFree, $freeRun, $hangOrder) {
			$rem = array();
			foreach($gvRegs as $r) $rem[$r['id']] = max(0, $remain[$r['id']]);
			$taken = array();   // [id_ngay][xe][t]
			$prevXe = array();
			$cur = null;
			$out = array();
			$switches = 0;
			for($t = $start; $t < 24; $t++)
			{
				// Giờ GV đã có lịch sẵn nằm giữa chuỗi vẫn tính là liên tục.
				if(isset($busy[$gv][$day][$t])) { if($out) continue; break; }
				$cands = array();
				if($cur !== null) $cands[] = $cur;
				foreach($gvRegs as $r) if($cur === null || $r['id'] !== $cur['id']) $cands[] = $r;
				$done = false;
				foreach($cands as $r)
				{
					if($rem[$r['id']] <= 0 || !isset($cfg[$day][$r['hang']]) || !$inWindow($r, $t)) continue;
					$c = $cfg[$day][$r['hang']];
					$pv = isset($prevXe[$r['hang']]) ? $prevXe[$r['hang']] : null;
					$xe = null;
					if($pv !== null && $xeFree($c, $pv, $t) && !isset($taken[$c['id']][$pv][$t])) $xe = $pv;
					else
					{
						$bestRun = 0;
						foreach($c['xe'] as $x)
						{
							if(isset($taken[$c['id']][$x][$t])) continue;
							$run = $freeRun($c, $x, $t);
							if($run > $bestRun) { $xe = $x; $bestRun = $run; }
						}
					}
					if($xe === null) continue;
					if($pv !== null && $pv !== $xe) $switches++;
					$taken[$c['id']][$xe][$t] = 1;
					$prevXe[$r['hang']] = $xe;
					$rem[$r['id']]--;
					$out[] = array('c' => $c, 'xe' => $xe, 't' => $t, 'r' => $r);
					$cur = $r;
					$done = true;
					break;
				}
				if(!$done) break;
			}
			return array($out, $switches);
		};

		$runChain = function($gv, $day) use (&$byGv, &$cfg, &$busy, &$remain, $simulate, $place, $hangOrder) {
			$gvRegs = array();
			foreach($byGv[$gv] as $r) if($remain[$r['id']] > 0 && isset($cfg[$day][$r['hang']])) $gvRegs[] = $r;
			if(!$gvRegs) return 0;
			usort($gvRegs, function($a, $b) use ($hangOrder) {
				return (isset($hangOrder[$a['hang']]) ? $hangOrder[$a['hang']] : 99) - (isset($hangOrder[$b['hang']]) ? $hangOrder[$b['hang']] : 99);
			});
			$minH = 24; $maxH = 0;
			foreach($gvRegs as $r) { $minH = min($minH, $cfg[$day][$r['hang']]['gio_bd']); $maxH = max($maxH, $cfg[$day][$r['hang']]['gio_kt']); }
			$best = null; $bestScore = null;
			for($s = $minH; $s < $maxH; $s++)
			{
				if(isset($busy[$gv][$day][$s])) continue;
				list($cells, $switches) = $simulate($gvRegs, $gv, $day, $s);
				if(!$cells) continue;
				$last = $cells[count($cells) - 1]['t'];
				$adjacent = (isset($busy[$gv][$day][$s - 1]) || isset($busy[$gv][$day][$last + 1])) ? 1 : 0;
				$score = array(count($cells), $adjacent, -$switches, -$s);
				if($bestScore === null || $score > $bestScore) { $best = $cells; $bestScore = $score; }
			}
			if(!$best) return 0;
			foreach($best as $cell) $place($cell['c'], $cell['xe'], $cell['t'], $cell['r']);
			return count($best);
		};

		$runParallel = function($gv, $day) use (&$byGv, &$cfg, &$busy, &$par, &$remain, $maxParallel, $inWindow, $pickXe, $place) {
			$placed = 0;
			if(empty($busy[$gv][$day])) return 0;
			foreach($byGv[$gv] as $r) if(($r['xac_nhan'] ?? '') === 'hai_ngay') return 0;
			$hours = array_keys($busy[$gv][$day]);
			sort($hours);
			foreach($byGv[$gv] as $r)
			{
				if(!isset($cfg[$day][$r['hang']])) continue;
				$c = $cfg[$day][$r['hang']];
				$prev = null;
				foreach($hours as $t)
				{
					if($remain[$r['id']] <= 0) break;
					if((isset($par[$gv][$day]) ? $par[$gv][$day] : 0) >= $maxParallel) return $placed;
					if($busy[$gv][$day][$t] !== 1 || !$inWindow($r, $t)) continue;
					$xe = $pickXe($c, $t, $prev);
					if($xe === null) continue;
					$place($c, $xe, $t, $r);
					$prev = $xe;
					$placed++;
				}
			}
			return $placed;
		};

		foreach($gvOrder as $gv)
		{
			$gvDays = $days;
			$pref = array();
			foreach($byGv[$gv] as $r) if(!empty($r['uu_tien_ngay']) && isset($cfg[$r['uu_tien_ngay']])) $pref[] = $r['uu_tien_ngay'];
			if($pref) { $pref = array_values(array_unique($pref)); sort($pref); $gvDays = array_merge($pref, array_values(array_diff($days, $pref))); }
			foreach($gvDays as $day)
			{
				$left = 0;
				foreach($byGv[$gv] as $r) if(isset($cfg[$day][$r['hang']])) $left += max(0, $remain[$r['id']]);
				if(!$left) continue;
				$runChain($gv, $day);
				$runParallel($gv, $day);
			}
		}
		/* Vòng lấp chỗ trống: giờ còn thiếu được xếp thêm khối mới vào chỗ trống phù hợp. */
		foreach($gvOrder as $gv)
			foreach($days as $day)
				while($runChain($gv, $day) > 0) {}

		$unplaced = array();
		foreach($remain as $id => $n) if($n > 0) $unplaced[$id] = $n;
		return array('new' => $new, 'unplaced' => $unplaced);
	}
}
