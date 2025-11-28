<?php
/**
 * ULID Generator Service
 *
 * Generates Universally Unique Lexicographically Sortable Identifiers
 */

declare(strict_types=1);

namespace App\Services;

class UlidGenerator
{
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const ENCODING_LEN = 32;
    private const TIME_LEN = 10;
    private const RANDOM_LEN = 16;

    /**
     * Generate a new ULID
     */
    public function generate(): string
    {
        $time = (int) (microtime(true) * 1000);
        $timeChars = $this->encodeTime($time);
        $randomChars = $this->encodeRandom();

        return $timeChars . $randomChars;
    }

    /**
     * Encode timestamp portion
     */
    private function encodeTime(int $time): string
    {
        $chars = '';

        for ($i = self::TIME_LEN - 1; $i >= 0; $i--) {
            $mod = $time % self::ENCODING_LEN;
            $chars = self::ENCODING[$mod] . $chars;
            $time = ($time - $mod) / self::ENCODING_LEN;
        }

        return $chars;
    }

    /**
     * Encode random portion
     */
    private function encodeRandom(): string
    {
        $chars = '';
        $bytes = random_bytes(10);

        for ($i = 0; $i < self::RANDOM_LEN; $i++) {
            $rand = ord($bytes[$i % 10]) % self::ENCODING_LEN;
            $chars .= self::ENCODING[$rand];
        }

        return $chars;
    }

    /**
     * Validate a ULID string
     */
    public function isValid(string $ulid): bool
    {
        if (strlen($ulid) !== 26) {
            return false;
        }

        for ($i = 0; $i < 26; $i++) {
            if (strpos(self::ENCODING, $ulid[$i]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract timestamp from ULID
     */
    public function getTimestamp(string $ulid): int
    {
        if (!$this->isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID');
        }

        $time = 0;
        $timeChars = substr($ulid, 0, self::TIME_LEN);

        for ($i = 0; $i < self::TIME_LEN; $i++) {
            $time = $time * self::ENCODING_LEN + strpos(self::ENCODING, $timeChars[$i]);
        }

        return $time;
    }
}
