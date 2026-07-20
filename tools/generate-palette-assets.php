<?php

declare(strict_types=1);

use NhanAZ\CustomToast\ToastColor;

$root = dirname(__DIR__);
$assetDirectory = $root . "/resources/CustomToast/textures/ui/custom_toast";

require_once $root . "/src/NhanAZ/CustomToast/ToastType.php";
require_once $root . "/src/NhanAZ/CustomToast/ToastColor.php";

if(!extension_loaded("gd")){
	throw new RuntimeException("The GD extension is required to generate palette assets");
}

$templates = [
	"round" => $assetDirectory . "/background_round.png",
	"square" => $assetDirectory . "/background_square.png"
];

$created = 0;
$preserved = 0;
foreach($templates as $corner => $templatePath){
	assertPngTemplate($templatePath);
	foreach(ToastColor::minecraftColors() as $color){
		$destination = $assetDirectory . "/background_{$corner}_{$color->assetName()}.png";
		if(is_file($destination)){
			++$preserved;
			continue;
		}

		createPaletteVariant($templatePath, $destination, $color->hex());
		++$created;
	}
}

echo "Minecraft palette assets ready: {$created} created, {$preserved} preserved." . PHP_EOL;

function assertPngTemplate(string $path) : void{
	$size = getimagesize($path);
	if($size === false || $size[0] !== 12 || $size[1] !== 12 || $size[2] !== IMAGETYPE_PNG){
		throw new RuntimeException("Expected a 12x12 PNG template: " . $path);
	}
}

function createPaletteVariant(string $templatePath, string $destination, string $hex) : void{
	$image = imagecreatefrompng($templatePath);
	if($image === false){
		throw new RuntimeException("Could not read PNG template: " . $templatePath);
	}

	imagealphablending($image, false);
	imagesavealpha($image, true);
	[$red, $green, $blue] = hexToRgb($hex);
	$replacement = imagecolorallocatealpha($image, $red, $green, $blue, 0);
	$replaced = 0;
	for($y = 0; $y < imagesy($image); ++$y){
		for($x = 0; $x < imagesx($image); ++$x){
			$rgba = imagecolorat($image, $x, $y);
			$alpha = ($rgba >> 24) & 0x7f;
			$currentRed = ($rgba >> 16) & 0xff;
			$currentGreen = ($rgba >> 8) & 0xff;
			$currentBlue = $rgba & 0xff;
			if($alpha === 0 && $currentRed === 85 && $currentGreen === 85 && $currentBlue === 85){
				imagesetpixel($image, $x, $y, $replacement);
				++$replaced;
			}
		}
	}

	if($replaced === 0){
		imagedestroy($image);
		throw new RuntimeException("Template does not contain the #555555 palette layer: " . $templatePath);
	}
	if(!imagepng($image, $destination, 9)){
		imagedestroy($image);
		throw new RuntimeException("Could not write generated asset: " . $destination);
	}
	imagedestroy($image);
}

/** @return array{int, int, int} */
function hexToRgb(string $hex) : array{
	if(preg_match('/^#([0-9A-F]{6})$/i', $hex, $matches) !== 1){
		throw new RuntimeException("Invalid RGB hex value: " . $hex);
	}
	$value = hexdec($matches[1]);
	return [($value >> 16) & 0xff, ($value >> 8) & 0xff, $value & 0xff];
}
