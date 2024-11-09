<?php

namespace deele\devkit\behaviors;

use deele\devkit\helpers\IdentifierCreator;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;

/**
 * Behavior of an ActiveRecord that stores data in a JSON column.
 *
 * @property ActiveRecordInterface $owner {@see Behavior::$owner}
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\behaviors
 */
class JsonDataStoreBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive timestamp value.
     * Set this property to false if you do not want to record the update time.
     */
    public string $dataAttribute = 'json_data';
    /**
     * @var mixed Default value for cases when there is no data stored yet
     */
    public mixed $defaultValue = '';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => $this->dataAttribute,
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->dataAttribute,
            ];
        }
    }

    /**
     * Replaces whole data array with given one, or sets it to null when empty value given.
     *
     * @param array $data
     * @return bool
     */
    public function setDataArray(array $data = []): bool
    {
        if ($data === null || $data === []) {
            $this->value = null;
        } else {
            try {
                $old = $this->value;
                \Yii::debug(
                    sprintf(
                        "Old data: %s \n new: %s",
                        \yii\helpers\VarDumper::dumpAsString($old),
                        \yii\helpers\VarDumper::dumpAsString($data)
                    ),
                    'DEBUG'
                );
                $this->value = Json::encode($data);


            } catch (InvalidArgumentException $e) {
                Yii::debug($e);
                return false;
            }
        }

        return true;
    }

    public static function preparedDataArray(string $value): ?array
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Json::decode($value);
        } catch (InvalidArgumentException $e) {
            Yii::warning(
                'Invalid JSON data: ' . \yii\helpers\VarDumper::dumpAsString($value) . '.',
                __METHOD__
            );
            Yii::debug($e);
        }
    }

    /**
     * @return array associative array with data attributes
     */
    public function getDataArray(): array
    {
        if ($this->value !== null) {
            $value = static::preparedDataArray($this->value);
        } else {
            $value = ArrayHelper::getValue($this->owner, $this->dataAttribute);
        }
        if ($value === null || $value === '') {
            $value = $this->getDefaultValue();
        }
        if ($value !== null && $value !== '' && is_string($value)) {
            return static::preparedDataArray($value);
        }
        return $value;
    }

    /**
     * @inheritdocs
     */
    protected function getValue($event = null): mixed
    {
        if ($this->value === null || $this->value === '') {
            return $this->getDefaultValue();
        }

        return parent::getValue($event);
//        $value = parent::getValue($event);
//        if ($value === null) {
//            $value = ArrayHelper::getValue($this->owner, $this->dataAttribute);
//        }
//        if ($value === null || $value === '') {
//            $value = $this->getDefaultValue($event);
//        }
//        if ($value !== null && $value !== '' && is_string($value)) {
//            try {
//                return Json::decode($value);
//            } catch (InvalidArgumentException $e) {
//                Yii::warning(
//                    'Invalid JSON data in attribute "' . $this->dataAttribute . '" of model "' . get_class($this->owner) . '": ' . \yii\helpers\VarDumper::dumpAsString($value) . '.',
//                    __METHOD__
//                );
//                Yii::debug($e);
//            }
//        }
//        return $value;
    }

    /**
     * Get default value
     * @return array|mixed
     * @since 2.0.14
     */
    protected function getDefaultValue(): mixed
    {
        if ($this->defaultValue instanceof \Closure || (is_array($this->defaultValue) && is_callable($this->defaultValue))) {
            return call_user_func($this->defaultValue);
        }

        return $this->defaultValue;
    }

    /**
     * Returns a specific value from the data array.
     *
     * @param string $key
     * @param mixed|null $defaultValue
     *
     * @return mixed
     */
    public function datum(
        string $key,
        mixed $defaultValue = null
    ): mixed {
        try {
            return ArrayHelper::getValue($this->getDataArray(), $key, $defaultValue);
        } catch (\Exception $e) {
            return $defaultValue;
        }
    }

    /**
     * Sets a specific value in the data array.
     *
     * @param string $path {@see ArrayHelper::setValue()}
     * @param mixed $value {@see ArrayHelper::setValue()}
     *
     * @return bool
     */
    public function setDatum(
        string $path,
        mixed $value = null
    ): bool {
        $data = $this->getDataArray();
        ArrayHelper::setValue($data, $path, $value);
        return $this->setDataArray($data);
    }

    /**
     * Removes a specific key in the data array.
     *
     * @param string $key {@see ArrayHelper::remove()}
     *
     * @return bool
     */
    public function removeDatum(
        string $key
    ): bool {
        $data = $this->getDataArray();
        ArrayHelper::remove($data, $key);
        return $this->setDataArray($data);
    }
}
