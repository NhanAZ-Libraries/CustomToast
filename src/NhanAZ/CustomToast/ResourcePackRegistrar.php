<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Utils;
use RuntimeException;
use Throwable;
use ZipArchive;
use function array_unshift;
use function array_values;
use function count;
use function file_get_contents;
use function file_exists;
use function in_array;
use function is_dir;
use function mkdir;
use function rtrim;
use function str_starts_with;
use function strlen;
use function substr;
use function unlink;
use const DIRECTORY_SEPARATOR;

/** @internal */
final class ResourcePackRegistrar{
	private const RESOURCE_PREFIX = "CustomToast/";
	private const PACK_FILE = "CustomToast.mcpack";
	private const SOURCE_ONLY_ENTRIES = [
		"textures/ui/custom_toast/background_round.png",
		"textures/ui/custom_toast/background_round.json",
		"textures/ui/custom_toast/background_square.png",
		"textures/ui/custom_toast/background_square.json"
	];
	private const REQUIRED_ENTRIES = [
		"manifest.json",
		"pack_icon.png",
		"ui/_ui_defs.json",
		"ui/hud_screen.json",
		"ui/chat_screen.json",
		"textures/ui/custom_toast/icon_info.png",
		"textures/ui/custom_toast/icon_success.png",
		"textures/ui/custom_toast/icon_warning.png",
		"textures/ui/custom_toast/icon_error.png"
	];

	private ?ZippedResourcePack $pack = null;
	private ?string $packPath = null;
	private bool $originalRequiredSetting = false;
	private bool $forcingRequiredSetting = false;

	public function __construct(private readonly PluginBase $plugin){}

	public function register() : string{
		if($this->pack !== null){
			throw new RuntimeException("The CustomToast resource pack is already registered");
		}

		$packPath = $this->compile();
		try{
			$pack = new ZippedResourcePack($packPath);
		}catch(Throwable $e){
			@unlink($packPath);
			throw new RuntimeException("Could not load the generated CustomToast resource pack", 0, $e);
		}

		$manager = $this->plugin->getServer()->getResourcePackManager();
		$this->originalRequiredSetting = $manager->resourcePacksRequired();
		$stack = $manager->getResourceStack();
		foreach($stack as $existing){
			if($existing->getPackId() === $pack->getPackId()){
				unset($pack);
				@unlink($packPath);
				throw new RuntimeException("A resource pack with the CustomToast UUID is already registered");
			}
		}

		array_unshift($stack, $pack);
		try{
			$manager->setResourceStack($stack);
		}catch(Throwable $e){
			unset($pack);
			@unlink($packPath);
			throw new RuntimeException("Could not register the CustomToast resource pack", 0, $e);
		}

		$this->pack = $pack;
		$this->packPath = $packPath;
		return $packPath;
	}

	public function setForceResourcePack(bool $force) : void{
		if($this->pack === null){
			throw new RuntimeException("The CustomToast resource pack is not registered");
		}

		$manager = $this->plugin->getServer()->getResourcePackManager();
		if($force){
			if(!$manager->resourcePacksRequired()){
				$manager->setResourcePacksRequired(true);
				$this->forcingRequiredSetting = true;
			}
		}elseif($this->forcingRequiredSetting){
			if($manager->resourcePacksRequired()){
				$manager->setResourcePacksRequired($this->originalRequiredSetting);
			}
			$this->forcingRequiredSetting = false;
		}
	}

	public function unregister() : void{
		if($this->pack === null || $this->packPath === null){
			return;
		}

		$packPath = $this->packPath;
		$this->setForceResourcePack(false);
		$manager = $this->plugin->getServer()->getResourcePackManager();
		$stack = $manager->getResourceStack();
		foreach($stack as $index => $existing){
			if($existing === $this->pack){
				unset($stack[$index]);
			}
		}
		$manager->setResourceStack(array_values($stack));

		$this->pack = null;
		$this->packPath = null;
		$this->forcingRequiredSetting = false;
		if(file_exists($packPath) && !@unlink($packPath)){
			$this->plugin->getLogger()->warning("Could not remove the generated CustomToast resource pack: " . $packPath);
		}
	}

	private function compile() : string{
		$dataFolder = rtrim($this->plugin->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR;
		if(!is_dir($dataFolder) && !mkdir($dataFolder, 0755, true) && !is_dir($dataFolder)){
			throw new RuntimeException("Could not create plugin data folder: " . $dataFolder);
		}

		$packPath = $dataFolder . self::PACK_FILE;
		@unlink($packPath);

		$zip = new ZipArchive();
		$result = $zip->open($packPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if($result !== true){
			throw new RuntimeException("ZipArchive could not create {$packPath} (error {$result})");
		}

		$entries = [];
		/** @var array<string, \SplFileInfo> $resources */
		$resources = $this->plugin->getResources();
		foreach(Utils::stringifyKeys($resources) as $resourceKey => $resource){
			if(!str_starts_with($resourceKey, self::RESOURCE_PREFIX)){
				continue;
			}

			$sourceEntry = substr($resourceKey, strlen(self::RESOURCE_PREFIX));
			$entry = self::archiveEntryFor($sourceEntry);
			if($entry === null){
				continue;
			}
			if(in_array($entry, $entries, true)){
				$zip->close();
				@unlink($packPath);
				throw new RuntimeException("Two bundled resources map to the same pack entry: " . $entry);
			}

			$contents = file_get_contents($resource->getPathname());
			if($contents === false){
				$zip->close();
				@unlink($packPath);
				throw new RuntimeException("Could not read bundled resource: " . $resourceKey);
			}
			if(!$zip->addFromString($entry, $contents)){
				$zip->close();
				@unlink($packPath);
				throw new RuntimeException("Could not add bundled resource to the pack: " . $resourceKey);
			}
			$entries[] = $entry;
		}

		if(!$zip->close()){
			@unlink($packPath);
			throw new RuntimeException("Could not finish the generated CustomToast resource pack");
		}
		if(count($entries) === 0){
			@unlink($packPath);
			throw new RuntimeException("No files were found below resources/CustomToast");
		}
		foreach(self::REQUIRED_ENTRIES as $requiredEntry){
			if(!in_array($requiredEntry, $entries, true)){
				@unlink($packPath);
				throw new RuntimeException("The generated resource pack is missing " . $requiredEntry);
			}
		}
		foreach(ToastColor::minecraftColors() as $color){
			foreach(["round", "square"] as $corner){
				$requiredEntry = "textures/ui/custom_toast/background_{$corner}_{$color->value}.png";
				if(!in_array($requiredEntry, $entries, true)){
					@unlink($packPath);
					throw new RuntimeException("The generated resource pack is missing " . $requiredEntry);
				}
			}
		}

		return $packPath;
	}

	/**
	 * Source assets use official color names for designers. The compiled pack
	 * uses one-character aliases so JSON UI can select a texture without
	 * creating 58 duplicated controls.
	 */
	private static function archiveEntryFor(string $sourceEntry) : ?string{
		if(in_array($sourceEntry, self::SOURCE_ONLY_ENTRIES, true)){
			return null;
		}
		foreach(ToastColor::minecraftColors() as $color){
			foreach(["round", "square"] as $corner){
				$namedEntry = "textures/ui/custom_toast/background_{$corner}_{$color->assetName()}.png";
				if($sourceEntry === $namedEntry){
					return "textures/ui/custom_toast/background_{$corner}_{$color->value}.png";
				}
			}
		}
		return $sourceEntry;
	}
}
