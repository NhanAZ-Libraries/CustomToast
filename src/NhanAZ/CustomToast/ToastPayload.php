<?php

declare(strict_types=1);

namespace NhanAZ\CustomToast;

use InvalidArgumentException;
use function mb_strcut;
use function mb_strlen;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strtoupper;

/** @internal */
final class ToastPayload{
	public const MARKER = "%toast%";
	public const MAX_TITLE_BYTES = 96;
	private const PREFIX_SEPARATOR = "//";

	private function __construct(){}

	public static function encode(ToastType $type, ToastCornerStyle $cornerStyle, ToastColor $color, string $message, ?string $title, int $maxMessageBytes, bool $showIcon = true, ?string $glyph = null) : string{
		if($maxMessageBytes < 1){
			throw new InvalidArgumentException("maxMessageBytes must be at least 1");
		}
		if($glyph !== null && (mb_strlen($glyph, "UTF-8") !== 1 || $glyph === "\r" || $glyph === "\n" || $glyph === "\t")){
			throw new InvalidArgumentException("glyph must be exactly one visible Unicode code point");
		}

		$message = self::truncateUtf8(self::normaliseMessage($message), $maxMessageBytes);
		$title = $title === null ? "" : self::truncateUtf8(self::normaliseTitle($title), self::MAX_TITLE_BYTES);
		if($title === "" && $message === ""){
			throw new InvalidArgumentException("A toast must contain a title or a message");
		}

		$text = $title === "" ? $message : ($message === "" ? "§l" . $title . "§r" : "§l" . $title . "§r\n" . $message);
		$typeCode = $glyph !== null ? "g" : ($showIcon ? $type->value : strtoupper($type->value));
		return self::MARKER . $typeCode . $cornerStyle->value . $color->resolve($type)->value . self::PREFIX_SEPARATOR . ($glyph ?? "") . $text;
	}

	private static function normaliseTitle(string $text) : string{
		return str_replace(["\r", "\n", "\t"], " ", $text);
	}

	private static function normaliseMessage(string $text) : string{
		return str_replace("\t", " ", str_replace(["\r\n", "\r"], "\n", $text));
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
