<?php

namespace deele\devkit\interfaces;

/**
 * Describes what models with transitionable statuses interface should have in common
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\interfaces
 *
 * @property-read array $transitions {@see TransitionableStatusesInterface::getTransitions()}
 * @property-read string $statusTransitionTitle {@see TransitionableStatusesInterface::getStatusTransitionTitle()}
 */
interface TransitionableStatusesInterface
{

    /**
     * @param bool $withLabels
     * @return array with all available transitions
     */
    public function getTransitions(bool $withLabels = true): array;

    /**
     * @param int $toStatus
     * @param true $autoSave
     *
     * @return bool
     */
    public function transition(int $toStatus, bool $autoSave = true): bool;

    /**
     * Returns status transition title
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public function getStatusTransitionTitle(?string $language = null): ?string;
}
