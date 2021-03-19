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

    private static function device()
    {
        return Hash::crc32(Request::header('X-Device-Uuid'));
    }

    public static function unique(string $code)
    {
        $hash = md5($code);
        $path = storage_path('authorize');
        $file = storage_path("authorize/$hash.token");
        !is_dir($path) && mkdir($path);
        file_put_contents($file, $code);
        return $code;
    }

    public static function create(int $id, string $type = 'password', string $expire = '+1 min')
    {
        return self::unique(RC4::encrypt([
            $id,
            $type,
            self::ip(),
            self::device(),
            strtotime($expire),
        ]));
    }

    private static function parser(array $types)
    {
        $types = array_map(function ($type) {
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
        return (new self($code))->check($id, self::parser($types));
    }

    public $ip;
    public $id;
    public $type;
    public $hash;
    public $code;
    public $device;
    public $expire;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->hash = md5($code);
        $data = RC4::decrypt($code);
        # ------------------------------------
        if (isset($data) && is_array($data)) {
            list(
                $this->id,
                $this->type,
                $this->ip,
                $this->device,
                $this->expire
            ) = $data;
        }
    }

    public function isUnique()
    {
        $file = storage_path("authorize/{$this->hash}.token");
        if (file_exists($file) === true) {
            $code = file_get_contents($file);
            unlink($file);
            return $code === $this->code;
        } else {
            return false;
        }
    }

    public function validate(...$types)
    {
        return $this->isUnique() && $this->expire >= time() && self::ip() == $this->ip && self::device() == $this->device && (empty($types) || in_array($this->type, Arr::flatten($types)));
    }

    public function check(int $id, ...$types)
    {
        return $id == $this->id && $this->validate($types);
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
            'type' => $this->type,
            'device' => $this->device,
            'expire' => $this->expire,
        ];
    }
}