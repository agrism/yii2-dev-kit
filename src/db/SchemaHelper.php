<?php
/**
 * Contains \deele\devkit\db\SchemaHelper
 */

namespace deele\devkit\db;

use Yii;
use yii\helpers\Inflector;

/**
 * Class SchemaHelper
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\db
 */
class SchemaHelper
{
    public const FK_RESTRICT = 1;
    public const FK_CASCADE = 2;
    public const FK_SET_NULL = 3;
    public const FK_NO_ACTION = 4;

    /**
     * Creates foreign key type name based on index
     *
     * @param int $type of the key
     *
     * @return string
     */
    public static function createForeignKeyType(int $type): string
    {
        $map = [
            static::FK_RESTRICT  => 'RESTRICT',
            static::FK_CASCADE   => 'CASCADE',
            static::FK_SET_NULL  => 'SET NULL',
            static::FK_NO_ACTION => 'NO ACTION',
        ];
        return $map[$type];
    }

    /**
     * Creates foreign key name based on table name and column
     *
     * @param string $table the table
     * @param string|array $columns the column(s) that should be used to create the
     *   foreign key name.
     * @param string|null $name of the foreign key. Imploded column names is
     *   used when no name given.
     *
     * @return string
     */
    public static function createForeignKeyName(string $table, $columns, string $name = null): ?string
    {
        if (is_null($name)) {
            if (is_array($columns)) {
                $columnsStr = implode(
                    '_',
                    $columns
                );
            } else {
                $columnsStr = str_replace(
                    ',',
                    '_',
                    $columns
                );
            }
            $name = implode(
                '_',
                [
                    'fk',
                    Inflector::camelize($columnsStr),
                    Inflector::camelize(static::unPrefixedTable($table))
                ]
            );
        }
        if (strlen($name) > 64) {
            $name = substr(
                $name,
                0,
                64
            );
        }

        return $name;
    }

    /**
     * Creates index name based on table name and columns
     *
     * @param string|array $columns the column(s) that should be used to create
     *   the index name.
     * @param string|null $name of the index. Imploded column names is used when
     *   no name given.
     *
     * @return string
     */
    public static function createIndexName($columns, string $name = null): ?string
    {
        if (is_null($name)) {
            if (is_array($columns)) {
                foreach ($columns as &$column) {
                    $column = Inflector::camelize($column);
                }
                unset($column);
                $columnsStr = implode(
                    '_',
                    $columns
                );
            } else {
                $columnsStr = str_replace(
                    ',',
                    '_',
                    Inflector::camelize($columns)
                );
            }
            $name = 'idx_' . $columnsStr;
        }
        if (strlen($name) > 64) {
            $name = substr(
                $name,
                0,
                64
            );
        }

        return $name;
    }

    /**
     * Creates table name surrounded by {{% and }} used for table name prefixing
     *
     * @param string $table
     *
     * @return string
     */
    public static function prefixedTable(string $table): string
    {
        if (strpos($table, '{{%') !== 0) {
            return '{{%' . $table . '}}';
        }

        return $table;
    }

    /**
     * Creates table name without surrounded {{% and }} used for table name prefixing
     *
     * @param string $table
     *
     * @return string
     */
    public static function unPrefixedTable(string $table): string
    {
        if (strpos($table, '{{%') === 0) {
            return substr($table, 3, -2);
        }

        return $table;
    }

    /**
     * Returns true, if all given tables does exist
     *
     * @param string|array $tableNames
     * @param bool $prefixNames
     *
     * @return bool
     */
    public static function tablesExist($tableNames, bool $prefixNames = true): bool
    {
        if (is_string($tableNames)) {
            $tableNames = explode(
                ',',
                $tableNames
            );
        }
        foreach ($tableNames as $tableName) {
            $tableSchema = Yii::$app->db->schema->getTableSchema(
                ($prefixNames ? static::prefixedTable($tableName) : $tableName)
            );
            if ($tableSchema === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true, if all given columns for given table does exist
     *
     * @param string $tableName
     * @param array $columns
     * @param bool $prefixTableName
     *
     * @return bool
     */
    public static function columnsExist(string $tableName, array $columns, bool $prefixTableName = true): bool
    {
        if ($prefixTableName) {
            $tableName = static::prefixedTable($tableName);
        }
        $tableSchema = Yii::$app->db->schema->getTableSchema($tableName);
        if ($tableSchema === null) {
            return false;
        }
        foreach ($columns as $foreignKey) {
            if (!isset($tableSchema['columns'][$foreignKey])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true, if all given foreign keys for given table does exist
     *
     * @param string $tableName
     * @param array $foreignKeys
     * @param bool $createNames
     * @param bool $prefixTableName
     *
     * @return bool
     */
    public static function foreignKeysExist(
        string $tableName,
        array $foreignKeys,
        bool $createNames,
        bool $prefixTableName = true
    ): bool {
        if ($prefixTableName) {
            $tableName = static::prefixedTable($tableName);
        }
        $tableSchema = Yii::$app->db->schema->getTableSchema($tableName);
        if ($tableSchema === null) {
            return false;
        }
        foreach ($foreignKeys as $foreignKey) {
            if ($createNames) {
                $foreignKey = static::createForeignKeyName($tableName, $foreignKey);
            }
            if (!isset($tableSchema['foreignKeys'][$foreignKey])) {
                return false;
            }
        }

        return true;
    }
}
