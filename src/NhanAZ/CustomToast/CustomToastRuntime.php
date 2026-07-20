<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use LogicException;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

/** @internal */
final class CustomToastRuntime{
	private static ?Server $server = null;
	private static ?ResourcePackRegistrar $registrar = null;
	private static ?string $resourcePackPath = null;
	private static int $consumerCount = 0;
	private static int $forceConsumerCount = 0;

	private function __construct(){}

	public static function acquire(PluginBase $plugin, bool $forceResourcePack) : string{
		$server = $plugin->getServer();
		if(self::$registrar === null){
			$registrar = new ResourcePackRegistrar($plugin);
			$resourcePackPath = $registrar->register();
			self::$server = $server;
			self::$registrar = $registrar;
			self::$resourcePackPath = $resourcePackPath;
		}elseif(self::$server !== $server){
			throw new LogicException("CustomToast cannot be shared between different Server instances");
		}

		++self::$consumerCount;
		if($forceResourcePack){
			++self::$forceConsumerCount;
			self::$registrar->setForceResourcePack(true);
		}

		return self::$resourcePackPath ?? throw new LogicException("CustomToast resource pack path is unavailable");
	}

	public static function release(bool $forceResourcePack) : void{
		if(self::$registrar === null || self::$consumerCount < 1){
			throw new LogicException("CustomToast runtime has no active consumers");
		}

		--self::$consumerCount;
		if($forceResourcePack){
			if(self::$forceConsumerCount < 1){
				throw new LogicException("CustomToast force-resource-pack reference count is invalid");
			}
			--self::$forceConsumerCount;
			if(self::$forceConsumerCount === 0){
				self::$registrar->setForceResourcePack(false);
			}
		}

		if(self::$consumerCount === 0){
			self::$registrar->unregister();
			self::$server = null;
			self::$registrar = null;
			self::$resourcePackPath = null;
			self::$forceConsumerCount = 0;
		}
	}
}

