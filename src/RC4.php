<?php
namespace Byancode\Library;

class RC4
{
    public static function crypt(string $data_str, string $key_str = null)
    {
        $key = array();
        $data = array();
        $key_str = $key_str ?? env('APP_KEY');
        for ($i = 0; $i < strlen($key_str); $i++) {
            $key[] = ord($key_str{$i});
        }
        for ($i = 0; $i < strlen($data_str); $i++) {
            $data[] = ord($data_str{$i});
        }
        // prepare key
        $state = range(0, 255);

        $len = count($key);
        $index1 = $index2 = 0;
        for ($counter = 0; $counter < 256; $counter++) {
            $index2 = ($key[$index1] + $state[$counter] + $index2) % 256;
            $tmp = $state[$counter];
            $state[$counter] = $state[$index2];
            $state[$index2] = $tmp;
            $index1 = ($index1 + 1) % $len;
        }
        // rc4
        $len = count($data);
        $x = $y = 0;
        for ($counter = 0; $counter < $len; $counter++) {
            $x = ($x + 1) % 256;
            $y = ($state[$x] + $y) % 256;
            $tmp = $state[$x];
            $state[$x] = $state[$y];
            $state[$y] = $tmp;
            $data[$counter] ^= $state[($state[$x] + $state[$y]) % 256];
        }
        // convert output back to a string
        $data_str = "";
        for ($i = 0; $i < $len; $i++) {
            $data_str .= chr($data[$i]);
        }
        return $data_str;
    }
    public static function encode(string $str)
    {
        return U64::encode(self::crypt($str));
    }
    public static function decode(string $str)
    {
        return self::crypt(U64::decode($str));
    }

    public static function create($str)
    {
        return self::encode(json_encode($str));
    }
    public static function recovery(string $str, bool $assoc = true)
    {
        return json_decode(self::decode($str), $assoc);
    }

    public static function encrypt($data)
    {
        $data   = json_encode($data);
        $key    = Hash::crc32($data);
        $hash   = Hash::crc32($key);
        $data   = self::crypt("$key$data$hash");
        return  U64::encode($data);
    }
    public static function decrypt(string $data)
    {
        $data   = U64::decode($data);
        $data   = self::crypt($data);
        $hash   = substr($data, -8);
        $key    = substr($data, 0, 8);
        $data   = substr($data, 8, -8);
        return  Hash::crc32($data) == $key && Hash::crc32($key) == $hash ? json_decode($data, true) : null;
    }

    public static function token(int $number)
    {
        $random = substr(str_shuffle('qwertyuiopasdfghjklzxcvbnm'), 0, 2);
        $session = Hash::crc32(session_id());
        $source = "{$random}:{$session}:{$number}";
        return U64::encode(self::crypt($source, $session));
    }
    public static function utoken(string $source)
    {
        $session = Hash::crc32(session_id());
        list($random, $hash, $number) = explode(':', self::crypt(U64::decode($source), $session));
        return $hash === $session ? intval($number) : null;
    }
}
