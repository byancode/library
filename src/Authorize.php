<?php
namespace Byancode\Library;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class Authorize
{
    private static function ip()
    {
        return Hash::crc32(Request::ip());
    }

    private static function token()
    {
        return Hash::crc32(Request::server('HTTP_USER_AGENT'));
    }

    public static function create(int $id, string $type = 'password', string $expire = '+1 min')
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
        $types = array_map(function($type) {
            return \preg_split('/[^a-zA-Z0-9_]+/', $type);
        }, Arr::flatten($types));
        # ----------------------------------
        $types = Arr::flatten($types);
        # ----------------------------------
        $types = array_map('trim', $types);
        # ----------------------------------
        return array_filter($types);
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
        return $this->expire >= time() && self::ip() == $this->ip && self::token() == $this->token && (empty($types) || in_array($this->type, Arr::flatten($types)));
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
