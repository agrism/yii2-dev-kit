<?php
/**
 * Contains \deele\devkit\base\HasTypesTrait
 */

namespace deele\devkit\base;

use Exception;
use yii\db\AfterSaveEvent;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Class HasTypesTrait
 *
 * @property integer $type Type.
 *
 * @property-read array $types {@link HasTypesTrait::getTypes()}
 * @property-read string $typeTitle {@link HasTypesTrait::getTypeTitle()}
 *
 * Remember to add event listeners to your `ActiveRecord::init()`:
 * ~~~
 * public function init()
 * {
 *     $this->listenForTypeChanges();
 *     parent::init();
 * }
 * ~~~
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\base
 * @method save(bool $runValidation)
 * @method trigger(string $getEventAfterTypeChangeName, AfterTypeChangeEvent $param)
 */
trait HasTypesTrait
{

    /**
     * Returns the name of event that is triggered after type change
     *
     * @return string
     */
    public static function getEventAfterTypeChangeName(): string
    {
        return 'afterTypeChange';
    }

    /**
     * Returns possible values of "type" attribute along with value titles
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return array
     */
    public static function getTypes(?string $language = null): array
    {
        return [];
    }

    /**
     * Creates type title based on identifier
     *
     * @param int $type Type identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     * @throws Exception
     */
    public static function createTypeTitle(int $type, ?string $language = null)
    {
        return ArrayHelper::getValue(static::getTypes($language), $type);
    }

    /**
     * Returns current "type" attribute value title
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     * @throws Exception
     */
    public function getTypeTitle(?string $language = null): ?string
    {
        return static::createTypeTitle($this->type, $language);
    }

    /**
     * @param int $newType
     * @param bool|true $autoSave
     * @param bool|false $runValidation
     *
     * @return bool
     */
    public function changeType(int $newType, bool $autoSave = true, bool $runValidation = false): bool
    {
        $success = true;
        if ($this->type !== $newType && in_array($newType, $this->types, true)) {
            $this->type = $newType;
            if ($autoSave) {
                $success = $this->save($runValidation);
            }
        }

        return $success;
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function handleTriggersAfterTypeChange(AfterSaveEvent $event): void
    {
        if (array_key_exists('type', $event->changedAttributes)) {
            $this->trigger(
                static::getEventAfterTypeChangeName(),
                new AfterTypeChangeEvent([
                    'oldType' => $event->changedAttributes['type'],
                    'newType' => $this->type,
                ])
            );
        }
    }

    /**
     * Attaches event listeners to object to listen for update and create events to handle type change
     */
    public function listenForTypeChanges(): void
    {
        if ($this instanceof ActiveRecord) {
            $this->on(
                $this::EVENT_AFTER_INSERT,
                [$this, 'handleTriggersAfterTypeChange']
            );
            $this->on(
                $this::EVENT_AFTER_UPDATE,
                [$this, 'handleTriggersAfterTypeChange']
            );
        }
    }
}
