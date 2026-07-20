<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use InvalidArgumentException;
use LogicException;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

final class CustomToast{
	private const SOUND_NAME = "random.toast";

	private bool $closed = false;

	private function __construct(
		private readonly PluginBase $plugin,
		private readonly int $maxMessageBytes,
		private readonly bool $playSound,
		private readonly ToastCornerStyle $cornerStyle,
		private readonly ToastColor $color,
		private readonly bool $forceResourcePack,
		private readonly string $resourcePackPath
	){}

	public static function create(
		PluginBase $plugin,
		bool $forceResourcePack = true,
		int $maxMessageBytes = 256,
		bool $playSound = true,
		ToastCornerStyle $cornerStyle = ToastCornerStyle::ROUND,
		ToastColor $color = ToastColor::AUTO
	) : self{
		if($maxMessageBytes < 1){
			throw new InvalidArgumentException("maxMessageBytes must be at least 1");
		}

		$resourcePackPath = CustomToastRuntime::acquire($plugin, $forceResourcePack);
		return new self($plugin, $maxMessageBytes, $playSound, $cornerStyle, $color, $forceResourcePack, $resourcePackPath);
	}

	public function send(
		Player $player,
		ToastType $type,
		string $message,
		?string $title = null,
		?bool $playSound = null,
		?ToastCornerStyle $cornerStyle = null,
		?ToastColor $color = null
	) : void{
		$this->assertOpen();
		$payload = ToastPayload::encode(
			$type,
			$cornerStyle ?? $this->cornerStyle,
			$color ?? $this->color,
			$message,
			$title,
			$this->maxMessageBytes
		);
		if($playSound ?? $this->playSound){
			$position = $player->getPosition();
			$player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
				self::SOUND_NAME,
				$position->getX(),
				$position->getY(),
				$position->getZ(),
				1.0,
				1.0,
				null
			));
		}

		$packet = new TextPacket();
		$packet->type = TextPacket::TYPE_SYSTEM;
		$packet->needsTranslation = false;
		$packet->message = $payload;
		$packet->xboxUserId = "";
		$packet->platformChatId = "";
		$packet->filteredMessage = null;
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public function broadcast(
		ToastType $type,
		string $message,
		?string $title = null,
		?bool $playSound = null,
		?ToastCornerStyle $cornerStyle = null,
		?ToastColor $color = null
	) : int{
		$this->assertOpen();
		$count = 0;
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			$this->send($player, $type, $message, $title, $playSound, $cornerStyle, $color);
			++$count;
		}
		return $count;
	}

	public function getResourcePackPath() : string{
		$this->assertOpen();
		return $this->resourcePackPath;
	}

	public function isClosed() : bool{
		return $this->closed;
	}

	public function close() : void{
		if($this->closed){
			return;
		}

		CustomToastRuntime::release($this->forceResourcePack);
		$this->closed = true;
	}

	private function assertOpen() : void{
		if($this->closed){
			throw new LogicException("This CustomToast instance is closed");
		}
	}
}
