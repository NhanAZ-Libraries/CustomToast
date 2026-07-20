<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$requiredFiles = [
	"virion.yml",
	"composer.json",
	"LICENSE",
	"README.md",
	"ASSETS.md",
	"src/NhanAZ/CustomToast/CustomToast.php",
	"src/NhanAZ/CustomToast/CustomToastRuntime.php",
	"src/NhanAZ/CustomToast/ResourcePackRegistrar.php",
	"src/NhanAZ/CustomToast/ToastColor.php",
	"src/NhanAZ/CustomToast/ToastCornerStyle.php",
	"src/NhanAZ/CustomToast/ToastPayload.php",
	"src/NhanAZ/CustomToast/ToastType.php",
	"resources/CustomToast/manifest.json",
	"resources/CustomToast/pack_icon.png",
	"resources/CustomToast/ui/_ui_defs.json",
	"resources/CustomToast/ui/hud_screen.json",
	"resources/CustomToast/ui/chat_screen.json",
	"resources/CustomToast/textures/ui/custom_toast/background_round.png",
	"resources/CustomToast/textures/ui/custom_toast/background_round.json",
	"resources/CustomToast/textures/ui/custom_toast/background_square.png",
	"resources/CustomToast/textures/ui/custom_toast/background_square.json",
	"tools/generate-palette-assets.php"
];

foreach($requiredFiles as $relative){
	if(!is_file($root . "/" . $relative)){
		throw new RuntimeException("Missing required file: " . $relative);
	}
}

$customToastSource = file_get_contents($root . "/src/NhanAZ/CustomToast/CustomToast.php");
if($customToastSource === false || !str_contains($customToastSource, 'private const SOUND_NAME = "random.toast";')){
	throw new RuntimeException("CustomToast must use the built-in random.toast sound event");
}
if(is_dir($root . "/resources/CustomToast/sounds")){
	throw new RuntimeException("Custom sound assets must not be bundled; random.toast is provided by the Bedrock client");
}
foreach(["background.png", "background.json"] as $obsoleteBackground){
	if(file_exists($root . "/resources/CustomToast/textures/ui/custom_toast/" . $obsoleteBackground)){
		throw new RuntimeException("Obsolete single-background asset found: " . $obsoleteBackground);
	}
}

$virion = file_get_contents($root . "/virion.yml");
if($virion === false || preg_match('/^version:[ \t]*1\.0\.0\r?$/m', $virion) !== 1){
	throw new RuntimeException("virion.yml must use version 1.0.0");
}

$manifest = decodeJson($root . "/resources/CustomToast/manifest.json");
if(($manifest["header"]["version"] ?? null) !== [1, 0, 0]){
	throw new RuntimeException("Resource-pack header version must be 1.0.0");
}
if(($manifest["modules"][0]["version"] ?? null) !== [1, 0, 0]){
	throw new RuntimeException("Resource-pack module version must be 1.0.0");
}
if(($manifest["header"]["uuid"] ?? null) === ($manifest["modules"][0]["uuid"] ?? null)){
	throw new RuntimeException("Resource-pack header and module UUIDs must be different");
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root . "/resources/CustomToast")
);
foreach($iterator as $file){
	if($file->isFile() && str_ends_with(strtolower($file->getFilename()), ".json")){
		decodeJson($file->getPathname());
	}
	if($file->isFile() && str_starts_with(strtolower($file->getFilename()), "accent_")){
		throw new RuntimeException("Unused accent asset found: " . $file->getPathname());
	}
}

$iconSize = getimagesize($root . "/resources/CustomToast/pack_icon.png");
if($iconSize === false || $iconSize[0] !== 256 || $iconSize[1] !== 256 || $iconSize[2] !== IMAGETYPE_PNG){
	throw new RuntimeException("pack_icon.png must be a 256x256 PNG");
}
foreach(["info", "success", "warning", "error"] as $iconName){
	$iconPath = $root . "/resources/CustomToast/textures/ui/custom_toast/icon_{$iconName}.png";
	$toastIconSize = getimagesize($iconPath);
	if($toastIconSize === false || $toastIconSize[0] !== 32 || $toastIconSize[1] !== 32 || $toastIconSize[2] !== IMAGETYPE_PNG){
		throw new RuntimeException("icon_{$iconName}.png must be a 32x32 PNG");
	}
}
$forbiddenPrototypeIconHashes = [
	"info" => "e26d3ef40c4b3fb5291ed76a6381262753fdd608e597ba9925f525c87aa1d094",
	"success" => "d54bfb2e87691db733f9dc3923330050fe1dd7ead3629e76ef1c1175de050731",
	"warning" => "41dee045401faef5320780fdbed1792f794175fc9616a07ddec87447551c48da",
	"error" => "7247782c0376c6068ca95025d3051545ca683f5ecc98caf5778640182c231435"
];
foreach($forbiddenPrototypeIconHashes as $iconName => $forbiddenHash){
	$iconPath = $root . "/resources/CustomToast/textures/ui/custom_toast/icon_{$iconName}.png";
	if(hash_file("sha256", $iconPath) === $forbiddenHash){
		throw new RuntimeException("A removed prototype icon was reintroduced: icon_{$iconName}.png");
	}
}
foreach(["background_round.png", "background_square.png"] as $backgroundName){
	$backgroundSize = getimagesize($root . "/resources/CustomToast/textures/ui/custom_toast/" . $backgroundName);
	if($backgroundSize === false || $backgroundSize[0] !== 12 || $backgroundSize[1] !== 12){
		throw new RuntimeException($backgroundName . " must be a 12x12 PNG");
	}
}

require_once $root . "/src/NhanAZ/CustomToast/ToastType.php";
require_once $root . "/src/NhanAZ/CustomToast/ToastColor.php";
$colors = \NhanAZ\CustomToast\ToastColor::minecraftColors();
if(count($colors) !== 29){
	throw new RuntimeException("The Bedrock palette must contain exactly 29 concrete colors");
}
$expectedCodes = str_split("0123456789abcdefghijmnpqstuvw");
foreach($colors as $index => $color){
	if($color->value !== $expectedCodes[$index]){
		throw new RuntimeException("Unexpected Bedrock color-code order at index " . $index);
	}
	if(\NhanAZ\CustomToast\ToastColor::fromName($color->assetName()) !== $color || \NhanAZ\CustomToast\ToastColor::fromName("§" . $color->value) !== $color){
		throw new RuntimeException("Color parser does not resolve " . $color->assetName());
	}
	foreach(["round", "square"] as $corner){
		$assetName = "background_{$corner}_{$color->assetName()}.png";
		$assetPath = $root . "/resources/CustomToast/textures/ui/custom_toast/" . $assetName;
		$assetSize = getimagesize($assetPath);
		if($assetSize === false || $assetSize[0] !== 12 || $assetSize[1] !== 12 || $assetSize[2] !== IMAGETYPE_PNG){
			throw new RuntimeException($assetName . " must be a 12x12 PNG");
		}
		if(extension_loaded("gd")){
			assertPalettePixels(
				$root . "/resources/CustomToast/textures/ui/custom_toast/background_{$corner}.png",
				$assetPath,
				$color->hex()
			);
		}
	}
}

foreach(["background_round_neutral.png", "background_square_neutral.png"] as $legacyNeutralAsset){
	if(file_exists($root . "/resources/CustomToast/textures/ui/custom_toast/" . $legacyNeutralAsset)){
		throw new RuntimeException("Use the official dark_gray asset name instead of " . $legacyNeutralAsset);
	}
}
foreach(["round", "square"] as $corner){
	foreach($expectedCodes as $code){
		$legacyCodeAsset = "background_{$corner}_{$code}.png";
		if(file_exists($root . "/resources/CustomToast/textures/ui/custom_toast/" . $legacyCodeAsset)){
			throw new RuntimeException("Formatting codes are internal; source asset must use its official name instead of " . $legacyCodeAsset);
		}
	}
}

$hudSource = file_get_contents($root . "/resources/CustomToast/ui/hud_screen.json");
foreach(['"nineslice_size": 4', '"target_property_name": "#color_code"', '($texture_prefix + #color_code)', '"target_property_name": "#texture"', "'%.10s' * #text", "'%.12s' * #text"] as $requiredHudFragment){
	if($hudSource === false || !str_contains($hudSource, $requiredHudFragment)){
		throw new RuntimeException("HUD is missing dynamic palette support: " . $requiredHudFragment);
	}
}
$hud = decodeJson($root . "/resources/CustomToast/ui/hud_screen.json");
$variantBindings = $hud["custom_toast_variant"]["bindings"] ?? null;
if(!is_array($variantBindings)){
	throw new RuntimeException("HUD custom_toast_variant bindings are missing");
}
$capturesItemTextOnce = false;
foreach($variantBindings as $binding){
	if(
		is_array($binding) &&
		($binding["binding_type"] ?? null) === "collection" &&
		($binding["binding_name"] ?? null) === "#chat_text" &&
		($binding["binding_name_override"] ?? null) === "#text" &&
		($binding["binding_collection_name"] ?? null) === "chat_text_grid" &&
		($binding["binding_condition"] ?? null) === "once"
	){
		$capturesItemTextOnce = true;
		break;
	}
}
if(!$capturesItemTextOnce){
	throw new RuntimeException("Each toast must capture its collection text once so queued items keep their own texture");
}

$forbiddenNames = ["gala" . "xite", "mega" . "smp"];
$textIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach($textIterator as $file){
	if(!$file->isFile() || !in_array(strtolower($file->getExtension()), ["php", "json", "md", "yml", "yaml"], true)){
		continue;
	}
	$contents = file_get_contents($file->getPathname());
	if($contents === false){
		throw new RuntimeException("Could not read " . $file->getPathname());
	}
	foreach($forbiddenNames as $forbidden){
		if(str_contains(strtolower($contents), $forbidden)){
			throw new RuntimeException("Prototype server name found in " . $file->getPathname());
		}
	}
}

require_once $root . "/src/NhanAZ/CustomToast/ToastCornerStyle.php";
require_once $root . "/src/NhanAZ/CustomToast/ToastPayload.php";
foreach($colors as $color){
	$colorPayload = \NhanAZ\CustomToast\ToastPayload::encode(
		\NhanAZ\CustomToast\ToastType::INFO,
		\NhanAZ\CustomToast\ToastCornerStyle::ROUND,
		$color,
		"Color",
		null,
		256
	);
	if($colorPayload !== "%toast%ir" . $color->value . "//Color"){
		throw new RuntimeException("Payload does not preserve color code " . $color->value);
	}
}
$payload = \NhanAZ\CustomToast\ToastPayload::encode(
	\NhanAZ\CustomToast\ToastType::SUCCESS,
	\NhanAZ\CustomToast\ToastCornerStyle::ROUND,
	\NhanAZ\CustomToast\ToastColor::AUTO,
	"Phần thưởng | hằng ngày ✓",
	"Success",
	256
);
if(!str_starts_with($payload, "%toast%sra//Success\n")){
	throw new RuntimeException("Unicode title and line-break payload test failed");
}
if(!str_contains($payload, "Phần thưởng | hằng ngày ✓")){
	throw new RuntimeException("Unicode message or literal pipe payload test failed");
}
$squarePayload = \NhanAZ\CustomToast\ToastPayload::encode(
	\NhanAZ\CustomToast\ToastType::INFO,
	\NhanAZ\CustomToast\ToastCornerStyle::SQUARE,
	\NhanAZ\CustomToast\ToastColor::NEUTRAL,
	"Square",
	null,
	256
);
if($squarePayload !== "%toast%is8//Square"){
	throw new RuntimeException("Square-corner payload test failed");
}
$numberLeadingPayload = \NhanAZ\CustomToast\ToastPayload::encode(
	\NhanAZ\CustomToast\ToastType::INFO,
	\NhanAZ\CustomToast\ToastCornerStyle::ROUND,
	\NhanAZ\CustomToast\ToastColor::BLUE,
	"1BCD EFGH IJKL MNOP",
	null,
	256
);
$letterLeadingPayload = \NhanAZ\CustomToast\ToastPayload::encode(
	\NhanAZ\CustomToast\ToastType::INFO,
	\NhanAZ\CustomToast\ToastCornerStyle::ROUND,
	\NhanAZ\CustomToast\ToastColor::BLUE,
	"ABCD EFGH IJKL MNOP",
	null,
	256
);
if($numberLeadingPayload !== "%toast%ir9//1BCD EFGH IJKL MNOP"){
	throw new RuntimeException("Number-leading payload changed unexpectedly");
}
if($letterLeadingPayload !== "%toast%ir9//ABCD EFGH IJKL MNOP"){
	throw new RuntimeException("Letter-leading payload changed unexpectedly");
}
if(strlen($numberLeadingPayload) !== strlen($letterLeadingPayload)){
	throw new RuntimeException("A number-leading payload must not gain protocol width");
}

echo "CustomToast validation passed." . PHP_EOL;

/** @return array<string, mixed> */
function decodeJson(string $path) : array{
	$contents = file_get_contents($path);
	if($contents === false){
		throw new RuntimeException("Could not read JSON file: " . $path);
	}
	try{
		/** @var array<string, mixed> $decoded */
		$decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		return $decoded;
	}catch(JsonException $e){
		throw new RuntimeException(sprintf("Invalid JSON in %s: %s", str_replace('\\', '/', $path), $e->getMessage()), 0, $e);
	}
}

function assertPalettePixels(string $templatePath, string $variantPath, string $hex) : void{
	$template = imagecreatefrompng($templatePath);
	$variant = imagecreatefrompng($variantPath);
	if($template === false || $variant === false){
		throw new RuntimeException("Could not read palette image for pixel validation");
	}
	$replacement = hexdec(substr($hex, 1));
	for($y = 0; $y < 12; ++$y){
		for($x = 0; $x < 12; ++$x){
			$templatePixel = imagecolorat($template, $x, $y);
			$alpha = ($templatePixel >> 24) & 0x7f;
			$rgb = $templatePixel & 0xffffff;
			$expected = $alpha === 0 && $rgb === 0x555555 ? $replacement : $templatePixel;
			if(imagecolorat($variant, $x, $y) !== $expected){
				imagedestroy($template);
				imagedestroy($variant);
				throw new RuntimeException("Palette pixel mismatch in {$variantPath} at {$x},{$y}");
			}
		}
	}
	imagedestroy($template);
	imagedestroy($variant);
}
