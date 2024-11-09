<?php

namespace deele\devkit\interfaces;

/**
 * Describes what models with statuses interface should have in common
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\interfaces
 *
 * @property int|null $status {@see StatusesInterface::getStatus()} {@see StatusesInterface::setStatus()}
 * @property-read string|null $statusTitle {@see StatusesInterface::getStatusTitle()}
 */
interface StatusesInterface
{

    /**
     * Returns current "status" value
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Sets current "status" value
     *
     * @param int|null $status
     */
    public function setStatus(?int $status): void;

    /**
     * Returns current "status" attribute value title
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return string|null
     */
    public function getStatusTitle(?string $language = null): ?string;
}
