<?php
namespace Byancode\Library;

class Token {
	const prefix = '__token__';
	public static function exists(string $key = ''){
		return app('session')->exists(self::prefix . $key);
	}
	public static function get(string $key = ''){
		return $_SESSION[self::prefix . $key] ?? self::create($key);
	}
	public static function create(string $key = ''){
		$hash = md5(uniqid(self::prefix));
		app('session')->put(self::prefix . $key, $hash);
		return $hash;
	}
	public static function destroy(string $key = '') {
		app('session')->forge(self::prefix . $key);
	}
	public static function verify(string $token, string $key = ''){
		return ($token == session(self::prefix . $key));
	}
}