<?php
namespace Byancode\Library;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class Authentication
{
    private static function ip()
    {
        return Hash::crc32(Request::ip());
    }
    private static function token()
    {
        return Hash::crc32(Token::get('authorization'));
    }
    public static function create(int $id, string $type = 'auth', string $expire = '+1 min')
    {
        return RC4::encrypt([
            $id,
            $type,
            self::ip(),
            self::token(),
            strtotime($expire),
        ]);
    }
    private static function parser(array $types)
    {
        return Arr::flatten(array_map(function($type) {
            return \explode(',', $type);
        }, Arr::flatten($types)));
    }
    public static function verify(string $code, int $id, ...$types)
    {
        return (new self($code))->validate($id, self::parser($types));
    }

    public $ip;
    public $id;
    public $type;
    public $token;
    public $expire;

    public function __construct(string $code)
    {
        $data = RC4::decrypt($code);
        # ------------------------------------
        if (isset($data) && is_array($data)) {
            list(
                $this->id,
                $this->type,
                $this->ip,
                $this->token,
                $this->expire
            ) = $data;
        }
    }
    public function check(...$types)
    {
        return $this->expire >= time() && self::ip() == $this->ip && self::token() == $this->token && (!$types || in_array($this->type, self::parser($types)));
    }

    public function validate(int $id, ...$types)
    {
        return $id == $this->id && $this->check($types);
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
            'type' => $this->type,
            'token' => $this->token,
            'expire' => $this->expire,
        ];
    }
}
