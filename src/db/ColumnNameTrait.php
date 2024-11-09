<?php
/**
 * Contains \deele\devkit\db\ColumnNameTrait
 */

namespace deele\devkit\db;

/**
 * Trait ColumnName that implements \deele\devkit\db\HasColumnNameInterface
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\db
 */
trait ColumnNameTrait
{

    /**
     * @var string|null
     */
    protected ?string $_tableAlias = null;

    /**
     * @return string table alias
     */
    public function getAlias(): string
    {
        if (empty($this->_tableAlias)) {
            $this->alias();
        }
        return $this->_tableAlias;
    }

    /**
     * Should be overridden in child classes and return human readable, short table alias, for example "feaobjtyp" for
     * table with name "feature_object_type" (first three letters of each word).
     *
     * Creates random alias by default.
     *
     * @return string created table alias
     */
    public static function createAlias(): string
    {
        return \Yii::$app->security->generateRandomString(6);
    }

    /**
     * @param string $alias a preferred alias of the table, {@see ColumnNameTrait::createAlias()} will be used.
     *
     * @return self
     */
    public function alias($alias = ''): self
    {
        if (empty($alias)) {
            $alias = static::createAlias();
        }
        $this->_tableAlias = $alias;
        return parent::alias($alias);
    }

    /**
     * Returns column name from a current active query table taking in account current table alias.
     * If no alias is set, it will be set to the preferred alias {@see ColumnNameTrait::alias()}.
     * Helps to avoid ambiguous column names.
     *
     * @param string $column
     *
     * @return string
     */
    public function columnName(string $column): string
    {
        if (empty($this->_tableAlias)) {
            $this->alias();
        }
        return $this->_tableAlias . '.' . $column;
    }
}
