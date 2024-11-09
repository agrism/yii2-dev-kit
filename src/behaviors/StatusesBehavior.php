<?php

namespace deele\devkit\behaviors;

use deele\devkit\interfaces\StatusesInterface;
use Closure;
use deele\devkit\base\AfterStatusChangeEvent;
use Exception;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Statuses Behavior
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\behaviors
 *
 * @property ActiveRecord $owner
 */
class StatusesBehavior extends Behavior implements StatusesInterface
{
    /**
     * @var string the attribute that holds status value
     */
    public $attribute = 'status';

    /**
     * @var array of statuses and event names that should be triggered after given status has changed
     */
    public $afterStatusChangeEventMap = [];

    /**
     * @var callable[] of all possible statuses with their labels as anonymous functions
     */
    public $statuses = [];

    /**
     * Attaches event listeners to object to listen for insert and update events to handle status change
     */
    public function events(): array
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'handleTriggersAfterStatusChange',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'handleTriggersAfterStatusChange',
        ];
    }

    /**
     * @param int|null $status
     * @return string|null
     */
    protected function eventNameAfterStatusChange(?int $status): ?string
    {
        try {
            return ArrayHelper::getValue($this->afterStatusChangeEventMap, $status);
        } catch (Exception $e) {
            Yii::debug($e->getMessage());
        }
        return null;
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function handleTriggersAfterStatusChange(AfterSaveEvent $event): void
    {
        if (array_key_exists($this->attribute, $event->changedAttributes)) {
            $oldStatus = $event->changedAttributes[$this->attribute];
            $eventName = $this->eventNameAfterStatusChange($oldStatus);
            if ($eventName !== null) {
                $this->owner->trigger(
                    $eventName,
                    new AfterStatusChangeEvent([
                        'oldStatus' => $oldStatus,
                        'newStatus' => $this->getStatus(),
                    ])
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?int
    {
        return $this->owner->{$this->attribute};
    }

    /**
     * @inheritDoc
     */
    public function setStatus(?int $status): void
    {
        $this->owner->{$this->attribute} = $status;
    }

    /**
     * @param bool $withLabels
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     * @return array with all possible statuses
     */
    public function allStatuses(bool $withLabels = true, ?string $language = null): array
    {
        if ($withLabels) {
            return array_map(static function (Closure $labelFunction) use ($language) {
                return $labelFunction($language);
            }, $this->statuses);
        }

        return array_keys($this->statuses);
    }

    /**
     * Creates status title based on identifier
     *
     * @param int $status Status identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public function createStatusTitle(int $status, ?string $language = null): ?string
    {
        try {
            $labelFunction = ArrayHelper::getValue($this->statuses, $status);
            if ($labelFunction instanceof Closure) {
                return $labelFunction($language);
            }
        } catch (Exception $e) {
            Yii::debug($e->getMessage());
        }
        return $status;
    }

    /**
     * @inheritDoc
     */
    public function getStatusTitle(?string $language = null): ?string
    {
        return $this->createStatusTitle($this->getStatus(), $language);
    }
}
