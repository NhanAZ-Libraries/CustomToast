<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use LogicException;
use function array_values;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function trim;

/**
 * Minecraft Bedrock's complete legacy formatting-code color palette.
 *
 * AUTO and NEUTRAL are convenience choices. They are resolved to a concrete
 * Minecraft color before the toast payload is sent to the client.
 */
enum ToastColor: string{
	case AUTO = "*";
	case NEUTRAL = "_";

	case BLACK = "0";
	case DARK_BLUE = "1";
	case DARK_GREEN = "2";
	case DARK_AQUA = "3";
	case DARK_RED = "4";
	case DARK_PURPLE = "5";
	case GOLD = "6";
	case GRAY = "7";
	case DARK_GRAY = "8";
	case BLUE = "9";
	case GREEN = "a";
	case AQUA = "b";
	case RED = "c";
	case LIGHT_PURPLE = "d";
	case YELLOW = "e";
	case WHITE = "f";

	case MINECOIN_GOLD = "g";
	case MATERIAL_QUARTZ = "h";
	case MATERIAL_IRON = "i";
	case MATERIAL_NETHERITE = "j";
	case MATERIAL_REDSTONE = "m";
	case MATERIAL_COPPER = "n";
	case MATERIAL_GOLD = "p";
	case MATERIAL_EMERALD = "q";
	case MATERIAL_DIAMOND = "s";
	case MATERIAL_LAPIS = "t";
	case MATERIAL_AMETHYST = "u";
	case MATERIAL_RESIN = "v";
	case LIGHT_BLUE = "w";

	public static function fromName(string $name) : ?self{
		$name = strtolower(trim($name));
		if(str_starts_with($name, "§")){
			$name = substr($name, strlen("§"));
		}elseif(str_starts_with($name, "&")){
			$name = substr($name, 1);
		}
		$name = str_replace(["-", " "], "_", $name);

		$direct = self::tryFrom($name);
		if($direct !== null){
			return $direct;
		}

		return match($name){
			"auto", "automatic", "type" => self::AUTO,
			"neutral", "default" => self::NEUTRAL,
			"black" => self::BLACK,
			"dark_blue", "darkblue" => self::DARK_BLUE,
			"dark_green", "darkgreen" => self::DARK_GREEN,
			"dark_aqua", "darkaqua", "dark_cyan", "darkcyan" => self::DARK_AQUA,
			"dark_red", "darkred" => self::DARK_RED,
			"dark_purple", "darkpurple", "purple" => self::DARK_PURPLE,
			"gold", "orange" => self::GOLD,
			"gray", "grey" => self::GRAY,
			"dark_gray", "darkgray", "dark_grey", "darkgrey" => self::DARK_GRAY,
			"blue" => self::BLUE,
			"green", "lime" => self::GREEN,
			"aqua", "cyan" => self::AQUA,
			"red" => self::RED,
			"light_purple", "lightpurple", "pink", "magenta" => self::LIGHT_PURPLE,
			"yellow" => self::YELLOW,
			"white" => self::WHITE,
			"minecoin_gold", "minecoingold" => self::MINECOIN_GOLD,
			"material_quartz", "quartz", "warm_light_gray", "warm_light_grey" => self::MATERIAL_QUARTZ,
			"material_iron", "iron", "cool_light_gray", "cool_light_grey" => self::MATERIAL_IRON,
			"material_netherite", "netherite", "dark_brown" => self::MATERIAL_NETHERITE,
			"material_redstone", "redstone" => self::MATERIAL_REDSTONE,
			"material_copper", "copper", "brown" => self::MATERIAL_COPPER,
			"material_gold" => self::MATERIAL_GOLD,
			"material_emerald", "emerald" => self::MATERIAL_EMERALD,
			"material_diamond", "diamond" => self::MATERIAL_DIAMOND,
			"material_lapis", "lapis", "dark_teal" => self::MATERIAL_LAPIS,
			"material_amethyst", "amethyst" => self::MATERIAL_AMETHYST,
			"material_resin", "resin" => self::MATERIAL_RESIN,
			"light_blue", "lightblue", "party_blue", "partyblue", "party" => self::LIGHT_BLUE,
			default => null
		};
	}

	public function resolve(ToastType $type) : self{
		return match($this){
			self::AUTO => match($type){
				ToastType::INFO => self::BLUE,
				ToastType::SUCCESS => self::GREEN,
				ToastType::WARNING => self::YELLOW,
				ToastType::ERROR => self::RED
			},
			self::NEUTRAL => self::DARK_GRAY,
			default => $this
		};
	}

	public function hex() : string{
		return match($this){
			self::AUTO, self::NEUTRAL => throw new LogicException("Resolve convenience colors before requesting a hex value"),
			self::BLACK => "#000000",
			self::DARK_BLUE => "#0000AA",
			self::DARK_GREEN => "#00AA00",
			self::DARK_AQUA => "#00AAAA",
			self::DARK_RED => "#AA0000",
			self::DARK_PURPLE => "#AA00AA",
			self::GOLD => "#FFAA00",
			self::GRAY => "#AAAAAA",
			self::DARK_GRAY => "#555555",
			self::BLUE => "#5555FF",
			self::GREEN => "#55FF55",
			self::AQUA => "#55FFFF",
			self::RED => "#FF5555",
			self::LIGHT_PURPLE => "#FF55FF",
			self::YELLOW => "#FFFF55",
			self::WHITE => "#FFFFFF",
			self::MINECOIN_GOLD => "#DDD605",
			self::MATERIAL_QUARTZ => "#E3D4D1",
			self::MATERIAL_IRON => "#CECACA",
			self::MATERIAL_NETHERITE => "#443A3B",
			self::MATERIAL_REDSTONE => "#971607",
			self::MATERIAL_COPPER => "#B4684D",
			self::MATERIAL_GOLD => "#DEB12D",
			self::MATERIAL_EMERALD => "#47A036",
			self::MATERIAL_DIAMOND => "#2CBAA8",
			self::MATERIAL_LAPIS => "#21497B",
			self::MATERIAL_AMETHYST => "#9A5CC6",
			self::MATERIAL_RESIN => "#EB7114",
			self::LIGHT_BLUE => "#8BB3FF"
		};
	}

	public function formattingCode() : string{
		if($this === self::AUTO || $this === self::NEUTRAL){
			throw new LogicException("AUTO and NEUTRAL do not have their own Minecraft formatting codes");
		}
		return "§" . $this->value;
	}

	/** The official lowercase color name used by editable source assets. */
	public function assetName() : string{
		if($this === self::AUTO || $this === self::NEUTRAL){
			throw new LogicException("AUTO and NEUTRAL do not have their own palette assets");
		}
		return strtolower($this->name);
	}

	/** @return list<self> */
	public static function minecraftColors() : array{
		return array_values(array_filter(
			self::cases(),
			static fn(self $color) : bool => $color !== self::AUTO && $color !== self::NEUTRAL
		));
	}
}
