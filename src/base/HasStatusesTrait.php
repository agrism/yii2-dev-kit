<?php
/**
 * Contains \deele\devkit\base\HasStatusesTrait
 */

namespace deele\devkit\base;

use Exception;
use Yii;
use yii\base\Component;
use yii\db\AfterSaveEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * Class HasStatusesTrait
 *
 * @property integer $status Status.
 *
 * @property-read string $statusTitle {@link HasStatusesTrait::getStatusTitle()}
 *
 * Remember to add event listeners to your `ActiveRecord::init()`:
 * ~~~
 * public function init()
 * {
 *     $this->listenForStatusChanges();
 *     parent::init();
 * }
 * ~~~
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\base
 */
trait HasStatusesTrait
{

    /**
     * Returns the name of event that is triggered after status change
     *
     * @return string
     */
    public static function getEventAfterStatusChangeName(): string
    {
        return 'afterStatusChange';
    }

    /**
     * Returns possible values of "status" attribute along with value titles
     *
     * @param bool $withLabels return statuses with translated labels or plain array
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return array
     */
    public static function allStatuses(bool $withLabels = true, ?string $language = null): array
    {
        return [];
    }

    /**
     * Creates status title based on identifier
     *
     * @param int $status Status identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public static function createStatusTitle(int $status, string $language = null): ?string
    {
        try {
            return ArrayHelper::getValue(static::allStatuses(true, $language), $status);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Returns current "status" attribute value title
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public function getStatusTitle(string $language = null): ?string
    {
        return static::createStatusTitle($this->status, $language);
    }

    /**
     * @param int $newStatus
     * @param bool|true $autoSave
     * @param bool|false $runValidation
     *
     * @return bool
     */
    public function changeStatus(int $newStatus, bool $autoSave = true, bool $runValidation = false): bool
    {
        $success = true;
        if ($this->status !== $newStatus) {
            if (in_array($newStatus, static::allStatuses(false), true)) {
                $this->status = $newStatus;
                if ($autoSave && method_exists($this, 'save')) {
                    $success = $this->save($runValidation);
                }
            } else {
                $success = false;
                if (class_exists('Yii')) {
                    Yii::error(
                        'Invalid status: ' .
                        VarDumper::dumpAsString($newStatus)
                    );
                }
            }
        }

        return $success;
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function handleTriggersAfterStatusChange(AfterSaveEvent $event): void
    {
        if (array_key_exists('status', $event->changedAttributes)) {
            $this->trigger(
                static::getEventAfterStatusChangeName(),
                new AfterStatusChangeEvent([
                    'oldStatus' => $event->changedAttributes['status'],
                    'newStatus' => $this->status,
                ])
            );
        }
    }

    /**
     * Attaches event listeners to object to listen for update and create events to handle status change
     *
     * This should be called from AR init function
     */
    public function listenForStatusChanges(): void
    {
        if ($this instanceof Component) {
            $this->on(
                $this::EVENT_AFTER_INSERT,
                [$this, 'handleTriggersAfterStatusChange']
            );
            $this->on(
                $this::EVENT_AFTER_UPDATE,
                [$this, 'handleTriggersAfterStatusChange']
            );
        }
    }

    /**
     * Returns current "status" value
     *
     * @see StatusesStaticInterface::getStatus()
     *
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * Sets current "status"
     *
     * @see StatusesStaticInterface::setStatus()
     *
     * @param int|null $status
     */
    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    /**
     * Returns current "status" attribute value
     *
     * @see StatusesStaticInterface::status()
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return string|int|null
     */
    public static function status($value, bool $label = false, ?string $language = null)
    {
        $status = static::find()->byId($value)->asArray()->select('status')->scalar();
        if (!empty($status) && $label) {
            return static::createStatusTitle($status, $language);
        }
        return $status;
    }
}
