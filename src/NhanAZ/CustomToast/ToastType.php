<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use function strtolower;

enum ToastType: string{
	case INFO = "i";
	case SUCCESS = "s";
	case WARNING = "w";
	case ERROR = "e";

	public static function fromName(string $name) : ?self{
		return match(strtolower($name)){
			"i", "info" => self::INFO,
			"s", "success", "ok" => self::SUCCESS,
			"w", "warning", "warn" => self::WARNING,
			"e", "error", "err" => self::ERROR,
			default => null
		};
	}
}

