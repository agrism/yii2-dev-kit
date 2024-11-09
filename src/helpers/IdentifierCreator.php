<?php

namespace deele\devkit\helpers;

use Exception;
use RuntimeException;
use Yii;

class IdentifierCreator
{
    /**
     * @var bool will remove look-alike characters ("O", "0", "I", "|", "l" and "1") from the character set.
     */
    public bool $excludeLookAlikeCharacters = false;

    /**
     * @var bool will remove lowercase characters from the character set.
     */
    public bool $excludeLowercaseCharacters = false;

    /**
     * @var bool will not use same character more than once.
     */
    public bool $eachCharacterMustOccurAtMostOnce = false;

    /**
     * @var string|null identifier prefix
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     */
    public ?string $prefix = null;

    /**
     * @var string|null identifier suffix
     *
     * This is counted as part of unique identifier thus lowering number of possible identifiers in existence.
     */
    public ?string $suffix = null;

    /**
     * @var string all identifier characters
     */
    public string $charset = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * The string generated matches [A-Za-z0-9_-]+.
     * Note that output may not be ASCII.
     *
     * @see generateRandomString() if you need a string.
     *
     * @param int $length the number of bytes to generate
     *
     * @return string the generated random bytes
     *
     * @throws RuntimeException if wrong length is specified or there is no random generator installed in the system
     * @throws Exception on failure.
     */
    public function generateRandomKey(int $length = 32): string
    {
        if (!is_int($length)) {
            throw new RuntimeException('First parameter ($length) must be an integer');
        }

        if ($length < 1) {
            throw new RuntimeException('First parameter ($length) must be greater than 0');
        }

        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (function_exists('mcrypt_create_iv')) {
            return mcrypt_create_iv($length);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        }

        throw new RuntimeException('System does not have available random generators');
    }

    /**
     * @param int $maximumLength
     *
     * @return string
     *
     * @throws InvalidArgumentException if wrong length is specified
     * @throws Exception on failure.
     */
    public function generate(int $maximumLength = 10): string
    {
        $charset = $this->charset;
        if ($this->excludeLowercaseCharacters) {
            $charset = preg_replace('~[^\p{Lu}]+~u', '', $charset);
        }
        if ($this->excludeLookAlikeCharacters) {
            $charset = preg_replace('~[^O0I|l1]+~u', '', $charset);
        }
        return $this->generateFromCharset($charset, $maximumLength);
    }

    /**
     * @param string $charset
     * @param int $maximumLength
     *
     * @return string
     *
     * @throws InvalidArgumentException if wrong length is specified
     * @throws Exception on failure.
     */
    public function generateFromCharset(string $charset, int $maximumLength = 10): string
    {
        $randomString = $this->prefix;
        $suffixLength = (!empty($this->suffix) ? mb_strlen($this->suffix) : 0);
        $i = 0;
        while (strlen($randomString) + $suffixLength < $maximumLength) {
            $randomChar = $this->generateRandomKey(1);
            if ($randomChar === null) {
                break;
            }
            if (strpos($charset, $randomChar) &&
                (
                    $this->eachCharacterMustOccurAtMostOnce === false ||
                    ($this->eachCharacterMustOccurAtMostOnce && strpos($randomString, $randomChar) === false)
                )
            ) {
                $randomString .= $randomChar;
            }
            $i++;
        }
        Yii::debug(
            sprintf('Generating new identifier took %d steps', $i),
            static::class
        );

        return $randomString . ($this->suffix ?? '');
    }
}
