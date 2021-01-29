<?php

namespace Byancode\Library;

class U2FA
{
    /**
     * @var string
     */
    private $secret;
    /**
     * @var int
     */
    private $passCodeLength;
    /**
     * @var int
     */
    private $pinModulo;
    /**
     * @var \DateTimeInterface
     */
    private $now;
    /**
     * @var int
     */
    public $codePeriod = 30;
    /**
     * @param string                  $secret
     * @param int                     $seconds
     * @param int                     $passCodeLength
     * @param \DateTimeInterface|null $now
     */
    public function __construct(string $secret, int $seconds = 30, int $passCodeLength = 6, \DateTimeInterface $now = null)
    {
        $this->secret = $secret;
        $this->codePeriod = $seconds;
        $this->passCodeLength = $passCodeLength;
        $this->pinModulo = 10 ** $passCodeLength;
        $this->now = $now ?? new \DateTimeImmutable();
    }

    /**
     * @param string $code
     */
    public function check($code): bool
    {
        $result = 0;
        // current period
        $result += hash_equals($this->getCode($this->now), $code);
        // previous period, happens if the user was slow to enter or it just crossed over
        $dateTime = new \DateTimeImmutable('@' . ($this->now->getTimestamp() - $this->codePeriod));
        $result += hash_equals($this->getCode($dateTime), $code);
        // next period, happens if the user is not completely synced and possibly a few seconds ahead
        $dateTime = new \DateTimeImmutable('@' . ($this->now->getTimestamp() + $this->codePeriod));
        $result += hash_equals($this->getCode($dateTime), $code);
        # -----------------
        return $result > 0;
    }
    /**
     *
     * @param float|string|int|null|\DateTimeInterface $time
     */
    public function getCode($time = null): string
    {
        if (null === $time) {
            $time = $this->now;
        }
        if ($time instanceof \DateTimeInterface) {
            $timeForCode = floor($time->getTimestamp() / $this->codePeriod);
        } else {
            @trigger_error(
                'Passing anything other than null or a DateTimeInterface to $time is deprecated as of 2.0 ' .
                'and will not be possible as of 3.0.',
                E_USER_DEPRECATED
            );
            $timeForCode = $time;
        }
        $timeForCode = str_pad(pack('N', $timeForCode), 8, chr(0), STR_PAD_LEFT);
        $hash = hash_hmac('sha1', $timeForCode, $this->secret, true);
        $offset = ord(substr($hash, -1));
        $offset &= 0xF;
        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
        return str_pad((string) ($truncatedHash % $this->pinModulo), $this->passCodeLength, '0', STR_PAD_LEFT);
    }
    /**
     * @param string $bytes
     * @param int    $start
     */
    private function hashToInt(string $bytes, int $start): int
    {
        return unpack('N', substr(substr($bytes, $start), 0, 4))[1];
    }
    # -------------------------------------------------------
    public static function create(string $secret, int $seconds = 30, int $length = 6)
    {
        return (new self($secret, $seconds, $length))->getCode();
    }
    public static function verify(string $secret, string $code, int $seconds = 30, int $length = 6)
    {
        return (new self($secret, $seconds, $length))->check($code);
    }
}
