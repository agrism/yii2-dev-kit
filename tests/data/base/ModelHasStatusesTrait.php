<?php

namespace deele\devkituinit\data\base;

use deele\devkit\base\HasStatusesTrait;
use deele\devkituinit\data\ar\ActiveRecord;
use Yii;

class ModelHasStatusesTrait extends ActiveRecord
{
    use HasStatusesTrait;

    public $id;
    public $status;

    public const STATUS_ONE = 1;
    public const STATUS_TWO = 2;
    public const STATUS_THREE = 3;

    public function init(): void
    {
        $this->listenForStatusChanges();
        parent::init();
    }

    public static function getStatuses(?string $language = null, bool $withLabels = true): array
    {
        if ($withLabels) {
            return [
                static::STATUS_ONE => Yii::t('app', 'One', [], $language),
                static::STATUS_TWO => Yii::t('app', 'Two', [], $language),
                static::STATUS_THREE => Yii::t('app', 'Three', [], $language),
            ];
        }
        return [
            static::STATUS_ONE,
            static::STATUS_TWO,
            static::STATUS_THREE,
        ];
    }

    public function save($runValidation = true, $attributeNames = null): bool
    {
        $changedAttributes = [];
        if ($this->status !== null) {
            $changedAttributes['status'] = null;
        }
        $this->afterSave($this->id === null, $changedAttributes);
        $this->id = 1;
        return true;
    }
}
