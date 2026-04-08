<?php
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
