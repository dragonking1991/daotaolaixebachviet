<?php
/**
 * VietQR bank transfer configuration
 * Change these values to match your receiving bank account
 */
define('VIETQR_BANK_BIN', '970436');           // Vietcombank
define('VIETQR_ACCOUNT_NO', 'IZPVEI60921BFG4RDY0'); // Account number

/**
 * Build VietQR EMVCo payload for bank transfer
 * Banking apps (BIDV, VCB, etc.) will parse this and open transfer screen
 *
 * @param string $accountNo  Recipient account number
 * @param string $bankBin    Bank BIN (e.g. 970436 for Vietcombank)
 * @param int    $amount     Amount in VND (0 = no amount)
 * @param string $message    Transfer description
 * @return string EMVCo QR payload string
 */
function buildVietQRPayload($accountNo, $bankBin, $amount = 0, $message = '')
{
	// TLV helper: ID (2 chars) + Length (2 chars zero-padded) + Value
	$tlv = function($id, $value) {
		return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
	};

	$payload = '';

	// Payload Format Indicator
	$payload .= $tlv('00', '01');

	// Point of Initiation Method: 12 = Dynamic QR
	$payload .= $tlv('01', '12');

	// Merchant Account Information (ID 38) - VietQR/NAPAS
	$guid = $tlv('00', 'A000000727');                          // NAPAS provider
	$bankInfo = $tlv('00', $bankBin) . $tlv('01', $accountNo); // BIN + Account
	$consumerInfo = $tlv('01', $bankInfo);
	$serviceCode = $tlv('02', 'QRIBFTTA');                     // Transfer service
	$merchantAccInfo = $guid . $consumerInfo . $serviceCode;
	$payload .= $tlv('38', $merchantAccInfo);

	// Transaction Currency: 704 = VND
	$payload .= $tlv('53', '704');

	// Transaction Amount
	if($amount > 0) {
		$payload .= $tlv('54', (string)(int)$amount);
	}

	// Country Code
	$payload .= $tlv('58', 'VN');

	// Additional Data Field (ID 62) - Purpose of Transaction (sub-ID 08)
	if(!empty($message)) {
		$addlData = $tlv('08', $message);
		$payload .= $tlv('62', $addlData);
	}

	// CRC placeholder (ID 63, length 04) - will be filled with CRC16
	$payload .= '6304';

	// Calculate CRC16-CCITT (xModem) and append
	$crc = crc16_ccitt_vietqr($payload);
	$payload .= strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));

	return $payload;
}

/**
 * CRC16-CCITT (0xFFFF) used by EMVCo / VietQR
 */
function crc16_ccitt_vietqr($data)
{
	$crc = 0xFFFF;
	for($i = 0; $i < strlen($data); $i++) {
		$crc ^= (ord($data[$i]) << 8);
		for($j = 0; $j < 8; $j++) {
			if($crc & 0x8000)
				$crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
			else
				$crc = ($crc << 1) & 0xFFFF;
		}
	}
	return $crc & 0xFFFF;
}

/**
 * Remove Vietnamese diacritics from string (for transfer message)
 */
function removeVietnameseDiacritics($str)
{
	$map = array(
		'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
		'ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
		'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
		'đ'=>'d',
		'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
		'ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
		'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
		'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
		'ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
		'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
		'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
		'ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
		'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
		'À'=>'A','Á'=>'A','Ả'=>'A','Ã'=>'A','Ạ'=>'A',
		'Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ẳ'=>'A','Ẵ'=>'A','Ặ'=>'A',
		'Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ậ'=>'A',
		'Đ'=>'D',
		'È'=>'E','É'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ẹ'=>'E',
		'Ê'=>'E','Ề'=>'E','Ế'=>'E','Ể'=>'E','Ễ'=>'E','Ệ'=>'E',
		'Ì'=>'I','Í'=>'I','Ỉ'=>'I','Ĩ'=>'I','Ị'=>'I',
		'Ò'=>'O','Ó'=>'O','Ỏ'=>'O','Õ'=>'O','Ọ'=>'O',
		'Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ổ'=>'O','Ỗ'=>'O','Ộ'=>'O',
		'Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ở'=>'O','Ỡ'=>'O','Ợ'=>'O',
		'Ù'=>'U','Ú'=>'U','Ủ'=>'U','Ũ'=>'U','Ụ'=>'U',
		'Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ử'=>'U','Ữ'=>'U','Ự'=>'U',
		'Ỳ'=>'Y','Ý'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Ỵ'=>'Y'
	);
	return strtr($str, $map);
}

/**
 * Generate QR code with logo overlay at center
 * Requires phpqrcode.php and GD extension
 */
function generateQRWithLogo($content, $filepath, $logoPath = null)
{
	require_once LIBRARIES.'phpqrcode.php';

	// Generate QR with high error correction (H = 30%) to survive logo overlay
	QRcode::png($content, $filepath, QR_ECLEVEL_H, 10, 4);

	// If no logo specified or file doesn't exist, return as-is
	if(!$logoPath || !file_exists($logoPath)) return;

	$qrPalette = imagecreatefrompng($filepath);
	if(!$qrPalette) return;

	// Convert palette QR image to truecolor for proper alpha blending
	$qrWidth = imagesx($qrPalette);
	$qrHeight = imagesy($qrPalette);
	$qrImage = imagecreatetruecolor($qrWidth, $qrHeight);
	imagecopy($qrImage, $qrPalette, 0, 0, 0, 0, $qrWidth, $qrHeight);
	imagedestroy($qrPalette);

	// Detect logo format
	$ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
	$logoImage = null;
	if($ext == 'png') $logoImage = imagecreatefrompng($logoPath);
	elseif($ext == 'jpg' || $ext == 'jpeg') $logoImage = imagecreatefromjpeg($logoPath);

	if(!$logoImage) return;

	$logoWidth = imagesx($logoImage);
	$logoHeight = imagesy($logoImage);

	// Logo should be ~20% of QR size
	$newLogoWidth = (int)($qrWidth * 0.2);
	$newLogoHeight = (int)($logoHeight * ($newLogoWidth / $logoWidth));

	$posX = (int)(($qrWidth - $newLogoWidth) / 2);
	$posY = (int)(($qrHeight - $newLogoHeight) / 2);

	// Draw white circle background behind logo
	$padding = 8;
	$radius = (int)(max($newLogoWidth, $newLogoHeight) / 2) + $padding;
	$centerX = $posX + (int)($newLogoWidth / 2);
	$centerY = $posY + (int)($newLogoHeight / 2);
	$white = imagecolorallocate($qrImage, 255, 255, 255);
	imagefilledellipse($qrImage, $centerX, $centerY, $radius * 2, $radius * 2, $white);

	// Resize logo onto a transparent canvas to preserve alpha
	$resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
	imagealphablending($resizedLogo, false);
	$transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
	imagefill($resizedLogo, 0, 0, $transparent);
	imagesavealpha($resizedLogo, true);
	imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0, $newLogoWidth, $newLogoHeight, $logoWidth, $logoHeight);

	// Overlay logo with alpha blending onto truecolor QR
	imagealphablending($qrImage, true);
	imagecopy($qrImage, $resizedLogo, $posX, $posY, 0, 0, $newLogoWidth, $newLogoHeight);

	// Save
	imagepng($qrImage, $filepath);
	imagedestroy($resizedLogo);
	imagedestroy($qrImage);
	imagedestroy($logoImage);
}
