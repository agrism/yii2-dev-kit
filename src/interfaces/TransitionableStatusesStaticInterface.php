<?php

namespace deele\devkit\interfaces;

/**
 * Describes what models with transitionable statuses interface should have in common
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\interfaces
 */
interface TransitionableStatusesStaticInterface extends TransitionableStatusesInterface
{

    /**
     * @param bool $withLabels
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     * @return array with all possible status transitions
     */
    public static function allTransitions(bool $withLabels = true, ?string $language = null): array;

    /**
     * @param StatusesInterface $model
     * @param bool $withLabels
     * @return array with all available transitions
     */
    public static function transitions(StatusesInterface $model, bool $withLabels = true): array;

    /**
     * @param StatusesInterface $fromModel
     * @param StatusesInterface $toModel
     *
     * @return bool
     */
    public static function validateTransition(StatusesInterface $fromModel, StatusesInterface $toModel): bool;

    /**
     * Creates status transition title based on identifier
     *
     * @param int $status Status identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public static function createStatusTransitionTitle(int $status, ?string $language = null): ?string;
}
