<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use function strtolower;

enum ToastCornerStyle: string{
	case ROUND = "r";
	case SQUARE = "s";

	public static function fromName(string $name) : ?self{
		return match(strtolower($name)){
			"r", "round", "rounded" => self::ROUND,
			"s", "square", "sharp" => self::SQUARE,
			default => null
		};
	}
}
