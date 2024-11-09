<?php

namespace deele\devkit\behaviors;

use deele\devkit\interfaces\StatusesInterface;
use deele\devkit\interfaces\TransitionableStatusesInterface;
use Closure;
use Exception;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Status Transitions Behavior
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\behaviors
 *
 * @property-read null|int $status
 * @property ActiveRecord|StatusesInterface $owner
 */
class StatusTransitionsBehavior extends Behavior implements TransitionableStatusesInterface
{
    /**
     * @var string the attribute that holds status value
     */
    public $attribute = 'status';

    /**
     * @var callable[] of all possible transitions with their labels as anonymous functions
     */
    public $transitions = [];

    /**
     * @var callable|null that will be called to validate if transition should be allowed, by default, all allowed
     */
    public $validateCallback;

    /**
     * Returns current "status" value
     *
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->owner->{$this->attribute};
    }

    /**
     * @param bool $withLabels
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     * @return array with all possible status transitions
     */
    public function allTransitions(bool $withLabels = true, ?string $language = null): array
    {
        if ($withLabels) {
            return array_map(static function (Closure $labelFunction) use ($language) {
                return $labelFunction($language);
            }, $this->transitions);
        }

        return array_keys($this->transitions);
    }

    /**
     * @param StatusesInterface $fromModel
     * @param StatusesInterface $toModel
     *
     * @return bool
     */
    public function validateTransition(StatusesInterface $fromModel, StatusesInterface $toModel): bool
    {
        if ($this->validateCallback instanceof Closure) {
            return call_user_func($this->validateCallback, $fromModel, $toModel);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTransitions(bool $withLabels = true): array
    {
        return array_filter(
            $this->allTransitions(false),
            function ($toStatus) {
                $toModel = clone $this->owner;
                $toModel->setStatus($toStatus);
                return $this->validateTransition($this->owner, $toModel);
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function transition(int $toStatus, bool $autoSave = true): bool
    {
        $success = true;
        $toModel = clone $this->owner;
        $toModel->setStatus($toStatus);
        if ($this->validateTransition($this->owner, $toModel)) {
            $this->owner->setStatus($toStatus);
            if ($autoSave) {
                $success = $this->owner->save();
            }
        }

        return $success;
    }

    /**
     * Creates status transition title based on identifier
     *
     * @param int $status Status identifier.
     * @param string|null $language the language code (e.g. `en-US`, `en`).
     *
     * @return null|string
     */
    public function createStatusTransitionTitle(int $status, ?string $language = null): ?string
    {
        try {
            $labelFunction = ArrayHelper::getValue($this->transitions, $status);
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
    public function getStatusTransitionTitle(?string $language = null): ?string
    {
        return $this->createStatusTransitionTitle($this->getStatus(), $language);
    }
}
