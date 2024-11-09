<?php
/**
 * Contains \deele\devkit\db\ColumnNameTrait
 */

namespace deele\devkit\db;

/**
 * Interface that defines an Active Record Query class that has columnName method.
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\db
 */
interface HasColumnNameInterface
{

    /**
     * @param string $column
     *
     * @return string
     */
    public function columnName(string $column): string;
}
