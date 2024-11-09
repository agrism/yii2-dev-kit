<?php

namespace deele\devkit\interfaces;

/**
 * Describes what models with statuses static interface should have in common
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\interfaces
 */
interface StatusesStaticInterface extends StatusesInterface
{

    /**
     * @param int|object|string $value
     * @param bool $label
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     * @return int|string|null
     */
    public static function status($value, bool $label = false, ?string $language = null);

    /**
     * @param bool $withLabels
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     * @return array with all possible statuses
     */
    public static function allStatuses(bool $withLabels = true, ?string $language = null): array;

    /**
     * Creates status title based on identifier
     *
     * @param int $status Status identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public static function createStatusTitle(int $status, ?string $language = null): ?string;
}
