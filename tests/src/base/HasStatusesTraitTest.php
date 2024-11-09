<?php

namespace deele\devkituinit\src\base;

use deele\devkit\base\AfterStatusChangeEvent;
use deele\devkituinit\data\base\ModelHasStatusesTrait;
use PHPUnit\Framework\TestCase;
use TypeError;
use yii\db\AfterSaveEvent;

require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';

class HasStatusesTraitTest extends TestCase
{

    /**
     * @covers HasStatusesTrait::getEventAfterStatusChangeName()
     */
    public function testGetEventAfterStatusChangeName(): void
    {
        $this->assertEquals(
            'afterStatusChange',
            ModelHasStatusesTrait::getEventAfterStatusChangeName()
        );
    }

    /**
     * All existing statuses without labels
     * @return array
     */
    protected function statuses(): array
    {
        return [
            ModelHasStatusesTrait::STATUS_ONE,
            ModelHasStatusesTrait::STATUS_TWO,
            ModelHasStatusesTrait::STATUS_THREE,
        ];
    }

    /**
     * All existing statuses with labels
     * @return array
     */
    protected function statusLabels(): array
    {
        return [
            ModelHasStatusesTrait::STATUS_ONE => 'One',
            ModelHasStatusesTrait::STATUS_TWO => 'Two',
            ModelHasStatusesTrait::STATUS_THREE => 'Three',
        ];
    }

    /**
     * @covers HasStatusesTrait::getStatuses()
     */
    public function testGetStatuses(): void
    {
        $this->assertEquals(
            $this->statuses(),
            ModelHasStatusesTrait::getStatuses(null, false)
        );
        $this->assertEquals(
            $this->statusLabels(),
            ModelHasStatusesTrait::getStatuses(null, true)
        );
    }

    /**
     * All non existing statuses
     * @return array
     */
    protected function invalidStatuses(): array
    {
        return [
            4,
            5,
            0,
        ];
    }

    /**
     * @covers HasStatusesTrait::createStatusTitle()
     */
    public function testCreateStatusTitle(): void
    {
        foreach ($this->statusLabels() as $status => $label) {
            $this->assertEquals(
                $label,
                ModelHasStatusesTrait::createStatusTitle($status)
            );
        }
        foreach ($this->invalidStatuses() as $status) {
            $this->assertNull(
                ModelHasStatusesTrait::createStatusTitle($status)
            );
        }
    }

    /**
     */
    public function testCreateInvalidStatusTitle(): void
    {
        $this->expectException(TypeError::class);

        ModelHasStatusesTrait::createStatusTitle('Four');
    }

    /**
     * @covers HasStatusesTrait::getStatusTitle()
     */
    public function testGetStatusTitle(): void
    {
        $model = new ModelHasStatusesTrait();
        foreach ($this->statusLabels() as $status => $label) {
            $model->status = $status;
            $this->assertEquals(
                $label,
                $model->getStatusTitle()
            );
        }
        foreach ($this->invalidStatuses() as $status) {
            $model->status = $status;
            $this->assertNull(
                $model->getStatusTitle()
            );
        }
    }

    /**
     * @covers HasStatusesTrait::changeStatus()
     */
    public function testChangeStatus(): void
    {
        foreach ($this->statuses() as $status) {
            $model = new ModelHasStatusesTrait();
            $eventAfterStatusChange = null;
            $model->on(
                $model::getEventAfterStatusChangeName(),
                static function ($event) use (&$eventAfterStatusChange) {
                    $eventAfterStatusChange = $event;
                }
            );
            $this->assertTrue(
                $model->changeStatus($status),
                'status change was allowed and automatic saving was triggered'
            );
            $this->assertEquals(
                $status,
                $model->status,
                'status was successfully changed'
            );
            $this->assertInstanceOf(
                AfterStatusChangeEvent::class,
                $eventAfterStatusChange,
                'AfterStatusChangeEvent is triggered after status change'
            );
        }
    }

    /**
     * @covers HasStatusesTrait::changeStatus()
     */
    public function testChangeToInvalidStatus(): void
    {
        $model = new ModelHasStatusesTrait();
        $model->status = ModelHasStatusesTrait::STATUS_ONE;
        foreach ($this->invalidStatuses() as $status) {
            $this->assertFalse($model->changeStatus($status));
            $this->assertEquals(
                ModelHasStatusesTrait::STATUS_ONE,
                $model->status
            );
        }
    }

    /**
     * @covers HasStatusesTrait::handleTriggersAfterStatusChange()
     */
    public function testHandleTriggersAfterStatusChange(): void
    {
        $model = new ModelHasStatusesTrait();
        $eventAfterStatusChange = null;
        $model->on(
            $model::getEventAfterStatusChangeName(),
            static function ($event) use (&$eventAfterStatusChange) {
                $eventAfterStatusChange = $event;
            }
        );
        $afterSaveEvent = new AfterSaveEvent([
            'sender' => $model,
            'changedAttributes' => [
                'status' => ModelHasStatusesTrait::STATUS_ONE
            ]
        ]);
        $model->handleTriggersAfterStatusChange($afterSaveEvent);
        $this->assertInstanceOf(
            AfterStatusChangeEvent::class,
            $eventAfterStatusChange,
            'AfterStatusChangeEvent is triggered after status change'
        );
    }

    /**
     * @covers HasStatusesTrait::handleTriggersAfterStatusChange()
     */
    public function testHandleTriggersAfterOtherAttributeChange(): void
    {
        $model = new ModelHasStatusesTrait();
        $eventAfterStatusChange = null;
        $model->on(
            $model::getEventAfterStatusChangeName(),
            static function ($event) use (&$eventAfterStatusChange) {
                $eventAfterStatusChange = $event;
            }
        );
        $afterSaveEvent = new AfterSaveEvent([
            'sender' => $model,
            'changedAttributes' => [
                'notStatus' => null
            ]
        ]);
        $model->handleTriggersAfterStatusChange($afterSaveEvent);
        $this->assertNull(
            $eventAfterStatusChange,
            'AfterStatusChangeEvent should not be triggered after change of other attributes than status'
        );
    }

    /**
     * @covers HasStatusesTrait::listenForStatusChanges()
     */
    public function testListenForStatusChanges(): void
    {
        $model = new ModelHasStatusesTrait();
        $this->assertTrue(
            $model->off(
                $model::EVENT_AFTER_INSERT,
                [$model, 'handleTriggersAfterStatusChange']
            ),
            'handleTriggersAfterStatusChange should be called on insert event'
        );
        $this->assertTrue(
            $model->off(
                $model::EVENT_AFTER_UPDATE,
                [$model, 'handleTriggersAfterStatusChange']
            ),
            'handleTriggersAfterStatusChange should be called on update event'
        );
    }
}
