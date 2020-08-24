<?php
namespace Byancode\Library;

class Hash
{
    public static function uuid($data)
    {
        return self::uuidFromMD5(self::md5($data));
    }
    public static function uuidFromFile($file)
    {
        return self::uuidFromMD5(md5_file($file));
    }
    public static function uuidFromMD5(string $hash)
    {
        return preg_replace('/^([[:xdigit:]]{8})([[:xdigit:]]{4})([[:xdigit:]]{4})([[:xdigit:]]{4})([[:xdigit:]]{12})$/', '$1-$2-$3-$4-$5', $hash);
    }
    public static function sha256($data)
    {
        return hash('sha256', json_encode($data) . env('APP_KEY'));
    }
    public static function adler32($data)
    {
        return hash('adler32', json_encode($data) . env('APP_KEY'));
    }
    public static function crc32($data)
    {
        return hash('crc32', json_encode($data) . env('APP_KEY'));
    }
    public static function sha1($data)
    {
        return hash_hmac('sha1', json_encode($data), env('APP_KEY'));
    }
    public static function md5($data)
    {
        return hash_hmac('md5', json_encode($data), env('APP_KEY'));
    }
    // ------------------------------------------------------------
    public static function xs($data)
    {
        return self::crc32($data);
    }
    public static function sm($data)
    {
        return self::crc32($data) . self::adler32($data);
    }
    public static function md($data)
    {
        return self::md5($data);
    }
    public static function lg($data)
    {
        return self::sha1($data);
    }
    // ------------------------------------------------------------
    public static function len10($data)
    {
        return substr(self::sm($data), 0, 10);
    }
}
