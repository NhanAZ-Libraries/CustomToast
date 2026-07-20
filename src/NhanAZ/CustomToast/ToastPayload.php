<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use InvalidArgumentException;
use function mb_strcut;
use function str_ends_with;
use function str_replace;
use function strlen;

/** @internal */
final class ToastPayload{
	public const MARKER = "%toast%";
	public const MAX_TITLE_BYTES = 96;
	private const PREFIX_SEPARATOR = "//";

	private function __construct(){}

	public static function encode(ToastType $type, ToastCornerStyle $cornerStyle, ToastColor $color, string $message, ?string $title, int $maxMessageBytes) : string{
		if($maxMessageBytes < 1){
			throw new InvalidArgumentException("maxMessageBytes must be at least 1");
		}

		$message = self::truncateUtf8(self::normaliseLine($message), $maxMessageBytes);
		$title = $title === null ? "" : self::truncateUtf8(self::normaliseLine($title), self::MAX_TITLE_BYTES);
		if($title === "" && $message === ""){
			throw new InvalidArgumentException("A toast must contain a title or a message");
		}

		$text = $title === "" ? $message : ($message === "" ? $title : $title . "\n" . $message);
		return self::MARKER . $type->value . $cornerStyle->value . $color->resolve($type)->value . self::PREFIX_SEPARATOR . $text;
	}

	private static function normaliseLine(string $text) : string{
		return str_replace(["\r", "\n", "\t"], " ", $text);
	}

	private static function truncateUtf8(string $text, int $maxBytes) : string{
		if(strlen($text) <= $maxBytes){
			return $text;
		}

		$result = mb_strcut($text, 0, $maxBytes, "UTF-8");
		if(str_ends_with($result, "§")){
			$result = mb_strcut($result, 0, strlen($result) - strlen("§"), "UTF-8");
		}
		return $result;
	}
}
