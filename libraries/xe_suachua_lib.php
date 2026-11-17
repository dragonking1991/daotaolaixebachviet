<?php
/* Nhật ký sửa chữa xe tập lái — dùng chung cho admin và cổng giáo viên.
 * Tách riêng khỏi daotao_lib.php để admin có thể nạp mà không khai báo trùng các hàm đào tạo. Cần global $d. */

if(!function_exists('dt_xe_suachua_ensure_table'))
{
	function dt_xe_suachua_ensure_table()
	{
		global $d;
		static $ready = false;
		if($ready) return;
		$d->rawQuery("CREATE TABLE IF NOT EXISTS #_dt_xe_suachua (
			id INT AUTO_INCREMENT PRIMARY KEY,
			id_xe INT NOT NULL,
			ngay_sua DATE NOT NULL,
			noi_dung VARCHAR(1000) NOT NULL,
			trang_thai VARCHAR(20) NOT NULL DEFAULT 'dang_sua',
			gara VARCHAR(255) DEFAULT NULL,
			so_km DECIMAL(10,2) DEFAULT NULL,
			chi_phi DECIMAL(14,0) DEFAULT NULL,
			ghi_chu TEXT DEFAULT NULL,
			ngay_baoduong DATE DEFAULT NULL,
			km_baoduong DECIMAL(10,2) DEFAULT NULL,
			nguoi_tao VARCHAR(100) DEFAULT '',
			nguoi_capnhat VARCHAR(100) DEFAULT '',
			anh_minh_chung TEXT DEFAULT NULL,
			ngaytao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ngaycapnhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY idx_dt_xe_suachua_history (id_xe, ngay_sua, id),
			KEY idx_dt_xe_suachua_next_date (ngay_baoduong)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		if(method_exists($d, 'getLastErrorCode') && $d->getLastErrorCode() !== '00000')
			throw new RuntimeException('Không thể tạo bảng nhật ký sửa chữa xe.');
		$column = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_xe_suachua' and column_name = 'anh_minh_chung' limit 0,1");
		if(method_exists($d, 'getLastErrorCode') && $d->getLastErrorCode() !== '00000')
			throw new RuntimeException('Không thể kiểm tra cột ảnh minh chứng.');
		if(!$column)
		{
			$d->rawQuery("ALTER TABLE #_dt_xe_suachua ADD COLUMN anh_minh_chung TEXT DEFAULT NULL");
			if(method_exists($d, 'getLastErrorCode') && $d->getLastErrorCode() !== '00000')
				throw new RuntimeException('Không thể bổ sung cột ảnh minh chứng.');
		}
		$ready = true;
	}

	function dt_xe_suachua_decode_images($value)
	{
		$images = json_decode((string)$value, true);
		if(!is_array($images)) return array();
		$images = array_slice($images, 0, 10);
		return array_values(array_filter($images, function($name) {
			return is_string($name) && preg_match('/^[a-f0-9]{32}\.jpg$/D', $name);
		}));
	}

	function dt_xe_suachua_image_path($filename)
	{
		if(!is_string($filename) || !preg_match('/^[a-f0-9]{32}\.jpg$/D', $filename)) return false;
		$path = ROOT.'/../private_upload/daotao/xe-sua-chua/'.$filename;
		return is_file($path) ? $path : false;
	}

	function dt_xe_suachua_image_authorized($idXe, $idRepair, $filename, $gvKey = null)
	{
		global $d;
		if((int)$idXe <= 0 || (int)$idRepair <= 0 || !is_string($filename)) return false;
		$sql = 'select r.anh_minh_chung from #_dt_xe_suachua r inner join #_dt_xe x on x.id = r.id_xe where r.id = ? and x.id = ?';
		$params = array((int)$idRepair, (int)$idXe);
		if($gvKey !== null)
		{
			$gvKey = trim((string)$gvKey);
			if($gvKey === '') return false;
			$sql .= ' and x.gv_key = ?';
			$params[] = $gvKey;
		}
		$sql .= ' limit 0,1';
		$repair = $d->rawQueryOne($sql, $params);
		return $repair && in_array($filename, dt_xe_suachua_decode_images($repair['anh_minh_chung'] ?? ''), true);
	}

	function dt_xe_suachua_upload_images($files, &$error = '')
	{
		$error = '';
		if(!is_array($files) || !isset($files['error'])) return array();
		$errors = is_array($files['error']) ? $files['error'] : array($files['error']);
		$tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : array($files['tmp_name'] ?? '');
		$sizes = is_array($files['size'] ?? null) ? $files['size'] : array($files['size'] ?? 0);
		$selected = array();
		foreach($errors as $index => $uploadError)
		{
			if((int)$uploadError === UPLOAD_ERR_NO_FILE) continue;
			$selected[] = $index;
		}
		if(count($selected) > 10)
		{
			$error = 'Mỗi nhật ký chỉ được tải lên tối đa 10 ảnh.';
			return false;
		}
		if(!$selected) return array();

		$directory = ROOT.'/../private_upload/daotao/xe-sua-chua/';
		if(!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory))
		{
			$error = 'Không thể tạo thư mục lưu ảnh minh chứng.';
			return false;
		}
		if(!function_exists('imagecreatefromstring') || !function_exists('imagejpeg'))
		{
			$error = 'Máy chủ chưa bật chức năng tối ưu ảnh. Vui lòng liên hệ quản trị viên.';
			return false;
		}
		$allowedMime = array('image/jpeg', 'image/png', 'image/webp');
		$saved = array();
		foreach($selected as $index)
		{
			$uploadError = (int)($errors[$index] ?? UPLOAD_ERR_NO_FILE);
			$tmpName = (string)($tmpNames[$index] ?? '');
			$fileSize = (int)($sizes[$index] ?? 0);
			if($uploadError !== UPLOAD_ERR_OK || $fileSize <= 0 || $fileSize > 5 * 1024 * 1024 || $tmpName === '' || !is_uploaded_file($tmpName))
			{
				$error = 'Ảnh tải lên không hợp lệ hoặc vượt quá 5 MB.';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			$imageInfo = @getimagesize($tmpName);
			$mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
			if(!in_array($mime, $allowedMime, true) || empty($imageInfo[0]) || empty($imageInfo[1]) || $imageInfo[0] > 8000 || $imageInfo[1] > 8000 || $imageInfo[0] * $imageInfo[1] > 12000000)
			{
				$error = 'Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP hợp lệ (tối đa 12 megapixel).';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			if(function_exists('finfo_open'))
			{
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$detectedMime = $finfo ? finfo_file($finfo, $tmpName) : false;
				if($finfo) finfo_close($finfo);
				if($detectedMime !== $mime)
				{
					$error = 'Định dạng ảnh tải lên không khớp với nội dung file.';
					dt_xe_suachua_remove_images($saved);
					return false;
				}
			}
			try { $filename = bin2hex(random_bytes(16)).'.jpg'; }
			catch(Exception $e)
			{
				$error = 'Không thể tạo tên file an toàn cho ảnh tải lên.';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			$imageBytes = @file_get_contents($tmpName);
			$source = $imageBytes !== false ? @imagecreatefromstring($imageBytes) : false;
			if(!$source)
			{
				$error = 'Không thể đọc nội dung ảnh minh chứng.';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			$sourceWidth = imagesx($source);
			$sourceHeight = imagesy($source);
			$scale = min(1, 1600 / max($sourceWidth, $sourceHeight));
			$width = max(1, (int)round($sourceWidth * $scale));
			$height = max(1, (int)round($sourceHeight * $scale));
			$optimized = imagecreatetruecolor($width, $height);
			if(!$optimized)
			{
				imagedestroy($source);
				$error = 'Máy chủ không đủ tài nguyên để tối ưu ảnh minh chứng.';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			$white = imagecolorallocate($optimized, 255, 255, 255);
			imagefill($optimized, 0, 0, $white);
			imagecopyresampled($optimized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
			imagedestroy($source);
			$destination = $directory.$filename;
			$written = false;
			foreach(array(82, 72, 62) as $quality)
			{
				$written = imagejpeg($optimized, $destination, $quality);
				clearstatcache(true, $destination);
				if($written && filesize($destination) <= 1024 * 1024) break;
			}
			imagedestroy($optimized);
			clearstatcache(true, $destination);
			if(!$written || !is_file($destination) || filesize($destination) > 1024 * 1024)
			{
				if(is_file($destination)) unlink($destination);
				$error = 'Ảnh sau tối ưu vẫn vượt quá 1 MB. Vui lòng chọn ảnh khác.';
				dt_xe_suachua_remove_images($saved);
				return false;
			}
			$saved[] = $filename;
		}
		return $saved;
	}

	function dt_xe_suachua_remove_images($images)
	{
		foreach(dt_xe_suachua_decode_images(json_encode((array)$images)) as $filename)
		{
			$path = ROOT.'/../private_upload/daotao/xe-sua-chua/'.$filename;
			if(is_file($path)) unlink($path);
		}
	}

	function dt_xe_suachua_date_value($value)
	{
		$value = trim((string)$value);
		if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $m)) return null;
		return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $value : null;
	}

	function dt_xe_suachua_decimal_value($value, $maxIntegerDigits, $scale)
	{
		$value = trim((string)$value);
		if($value === '') return null;
		$pattern = '/^\d{1,'.$maxIntegerDigits.'}'.($scale ? '(?:\.\d{1,'.$scale.'})?' : '').'$/D';
		if(!preg_match($pattern, $value)) return false;
		return $value;
	}

	function dt_xe_suachua_validate($input)
	{
		$invalidInput = array();
		$read = function($key, $default = '') use ($input, &$invalidInput) {
			if(!array_key_exists($key, $input)) return $default;
			if(!is_scalar($input[$key]))
			{
				$invalidInput[$key] = true;
				return '';
			}
			return trim((string)$input[$key]);
		};
		$values = array(
			'ngay_sua' => $read('ngay_sua'),
			'noi_dung' => $read('noi_dung'),
			'trang_thai' => $read('trang_thai', 'dang_sua'),
			'gara' => $read('gara'),
			'so_km' => $read('so_km'),
			'chi_phi' => $read('chi_phi'),
			'ghi_chu' => $read('ghi_chu'),
			'ngay_baoduong' => $read('ngay_baoduong'),
			'km_baoduong' => $read('km_baoduong'),
		);
		$errors = array();
		foreach($invalidInput as $key => $invalid) $errors[$key] = 'Giá trị không hợp lệ.';
		$repairDate = dt_xe_suachua_date_value($values['ngay_sua']);
		$maintenanceDate = dt_xe_suachua_date_value($values['ngay_baoduong']);
		if(!$repairDate) $errors['ngay_sua'] = 'Vui lòng nhập ngày sửa hợp lệ.';
		if($values['noi_dung'] === '') $errors['noi_dung'] = 'Vui lòng mô tả nội dung sửa chữa.';
		elseif(mb_strlen($values['noi_dung'], 'UTF-8') > 1000) $errors['noi_dung'] = 'Nội dung tối đa 1.000 ký tự.';
		if(!in_array($values['trang_thai'], array('dang_sua','hoan_tat'), true)) $errors['trang_thai'] = 'Vui lòng chọn trạng thái hợp lệ.';
		if(mb_strlen($values['gara'], 'UTF-8') > 255) $errors['gara'] = 'Tên gara tối đa 255 ký tự.';
		if(mb_strlen($values['ghi_chu'], 'UTF-8') > 5000) $errors['ghi_chu'] = 'Ghi chú tối đa 5.000 ký tự.';

		$km = dt_xe_suachua_decimal_value($values['so_km'], 8, 2);
		$kmNext = dt_xe_suachua_decimal_value($values['km_baoduong'], 8, 2);
		$cost = dt_xe_suachua_decimal_value($values['chi_phi'], 14, 0);
		if($km === false) $errors['so_km'] = 'Số km phải là số không âm, tối đa 8 chữ số và 2 số thập phân.';
		if($kmNext === false) $errors['km_baoduong'] = 'Km bảo dưỡng phải là số không âm, tối đa 8 chữ số và 2 số thập phân.';
		if($cost === false) $errors['chi_phi'] = 'Chi phí phải là số VND nguyên, tối đa 14 chữ số.';
		if($repairDate && $maintenanceDate && $maintenanceDate < $repairDate)
			$errors['ngay_baoduong'] = 'Ngày bảo dưỡng tiếp theo không được trước ngày sửa.';
		if($km !== false && $km !== null && $kmNext !== false && $kmNext !== null && (float)$kmNext <= (float)$km)
			$errors['km_baoduong'] = 'Km bảo dưỡng tiếp theo phải lớn hơn số km hiện tại.';

		$data = array(
			'ngay_sua' => $repairDate,
			'noi_dung' => $values['noi_dung'],
			'trang_thai' => $values['trang_thai'],
			'gara' => $values['gara'] === '' ? null : $values['gara'],
			'so_km' => $km === false ? null : $km,
			'chi_phi' => $cost === false ? null : $cost,
			'ghi_chu' => $values['ghi_chu'] === '' ? null : $values['ghi_chu'],
			'ngay_baoduong' => $maintenanceDate,
			'km_baoduong' => $kmNext === false ? null : $kmNext,
		);
		return array($data, $errors, $values);
	}
}
