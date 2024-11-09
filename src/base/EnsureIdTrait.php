<?php
/**
 * Contains \deele\devkit\base\EnsureIdTrait
 */

namespace deele\devkit\base;

/**
 * Trait EnsureIdTrait
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\base
 */
trait EnsureIdTrait
{

    /**
     * @param object|int $value
     *
     * @return int|object
     * @throws \InvalidArgumentException
     */
    public static function ensureId($value)
    {
        if ($value instanceof static) {
            $id = $value->id;
        } elseif (is_numeric($value)) {
            $id = (int) $value;
        }
        if (!isset($id) || is_null($id)) {
            throw new \InvalidArgumentException();
        }

        return $id;
    }
}
