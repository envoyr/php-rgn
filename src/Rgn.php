<?php

/*
 * This file is part of the Rgn package.
 *
 * Copyright (c) 2023 Robin van der Vleuten <robin@webstronauts.co>
 * Copyright (c) 2023 envoyr <hello@envoyr.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Envoyr\Rgn;

use Envoyr\Rgn\Exception\InvalidRgnStringException;
use Exception;

class Rgn
{
    public const ENCODING_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    public const ENCODING_LENGTH = 32;

    public const TIME_MAX = 281474976710655;
    public const TIME_LENGTH = 10;

    /**
     * @var string
     */
    private string $time;

    /**
     * @var string
     */
    private string $identifier;

    /**
     * @var bool
     */
    private bool $lowercase;

    private function __construct(string $time, string $randomness, bool $lowercase = false)
    {
        $this->time = $time;
        $this->identifier = $randomness;
        $this->lowercase = $lowercase;
    }

    /**
     * @param string $value
     * @param bool $lowercase
     * @return self
     * @throws InvalidRgnStringException
     */
    public static function fromString(string $value, bool $lowercase = false): self
    {
        // Convert to uppercase for regex. Doesn't matter for output later, that is determined by $lowercase.
        $value = strtoupper($value);

        if (!preg_match(sprintf('!^[%s]+$!', static::ENCODING_CHARS), $value)) {
            throw new InvalidRgnStringException('Invalid Rgn string (wrong characters): ' . $value);
        }

        return new static(substr($value, 0, static::TIME_LENGTH), substr($value, static::TIME_LENGTH), $lowercase);
    }

    /**
     * Create a Rgn using the given timestamp.
     * @param int $milliseconds Number of milliseconds since the UNIX epoch for which to generate this Rgn.
     * @param int $identifier
     * @param bool $lowercase True to output lowercase Rgns.
     * @return Rgn Returns a Rgn object for the given microsecond time.
     * @throws Exception
     */
    public static function fromTimestamp(int $milliseconds, int $identifier, bool $lowercase = false): self
    {
        $timeChars = '';
        $identifierChars = Rgn::encodeIdentifier($identifier);
        $encodingChars = static::ENCODING_CHARS;

        for ($i = static::TIME_LENGTH - 1; $i >= 0; $i--) {
            $mod = $milliseconds % static::ENCODING_LENGTH;
            $timeChars = $encodingChars[$mod].$timeChars;
            $milliseconds = ($milliseconds - $mod) / static::ENCODING_LENGTH;
        }

        return new static($timeChars, $identifierChars, $lowercase);
    }

    /**
     * @throws Exception
     */
    public static function generate(int $identifier, bool $lowercase = false): self
    {
        $now = (int) (microtime(true) * 1000);

        return static::fromTimestamp($now, $identifier, $lowercase);
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function isLowercase(): bool
    {
        return $this->lowercase;
    }

    public function toTimestamp(): int
    {
        return $this->decodeTime($this->time);
    }

    public function toIdentifierInt(): int
    {
        return $this->decodeIdentifier($this->identifier, true);
    }

    public function __toString(): string
    {
        return ($value = $this->time . $this->identifier) && $this->lowercase ? strtolower($value) : strtoupper($value);
    }

    private function decodeTime(string $time): int
    {
        $timeChars = str_split(strrev($time));
        $carry = 0;

        foreach ($timeChars as $index => $char) {
            if (($encodingIndex = strripos(static::ENCODING_CHARS, $char)) === false) {
                throw new InvalidRgnStringException('Invalid Rgn character: ' . $char);
            }

            $carry += ($encodingIndex * pow(static::ENCODING_LENGTH, $index));
        }

        if ($carry > static::TIME_MAX) {
            throw new InvalidRgnStringException('Invalid Rgn string: timestamp too large');
        }

        return $carry;
    }

    /**
     * @param $identifier
     * @return string
     */
    public static function encodeIdentifier($identifier): string
    {
        $out   =   '';
        $base  = strlen(self::ENCODING_CHARS);

        for ($t = ($identifier != 0 ? floor(log($identifier, $base)) : 0); $t >= 0; $t--) {
            $bcp = bcpow($base, $t);
            $a   = floor($identifier / $bcp) % $base;
            $out = $out . substr(self::ENCODING_CHARS, $a, 1);
            $identifier  = $identifier - ($a * $bcp);
        }

        return $out;
    }

    /**
     * @param $identifier
     * @return int
     */
    public function decodeIdentifier($identifier): int
    {
        $out   =   0;
        $base  = strlen(self::ENCODING_CHARS);

        $len = strlen($identifier) - 1;

        for ($t = $len; $t >= 0; $t--) {
            $bcp = bcpow($base, $len - $t);
            $out = $out + strpos(self::ENCODING_CHARS, substr($identifier, $t, 1)) * $bcp;
        }

        return $out;
    }
}