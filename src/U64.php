<?php
namespace Byancode\Library;

class U64
{
	// ---------------------------------------
	public static function mask(string $str)
	{
		return str_replace(['+','/'], ['-','_'], rtrim($str, '='));
	}
	public static function unmask(string $str)
	{
		$str = str_replace(['-','_'], ['+','/'], $str);
		switch (strlen($str) % 4) {
			case 2: $str .= '=='; break;
			case 3: $str .= '='; break;
		}
		return $str;
	}
	// ---------------------------------------
	public static function masker(string $str)
	{
		return str_replace(['+','/', '==', '='], ['-','_', '~', '.'], $str);
	}
	public static function unmasker(string $st)
	{
		return str_replace(['-','_', '~', '.'], ['+','/', '==', '='], $str);
	}
	// ---------------------------------------
	public static function encode(string $str)
	{
		return self::mask(base64_encode($str));
	}
	public static function decode(string $str, bool $strict = false)
	{
		return base64_decode(self::unmask($str), $strict);
	}
	// ---------------------------------------
}