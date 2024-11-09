<?php
/**
 * Contains \deele\devkit\base\AfterStatusChangeEvent
 */

namespace deele\devkit\base;

use yii\base\Event;

/**
 * Class AfterStatusChangeEvent
 *
 * @property \yii\db\ActiveRecord|HasStatusesTrait $sender
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\base
 */
class AfterStatusChangeEvent extends Event
{

    /**
     * @var int
     */
    public $newStatus;

    /**
     * @var int
     */
    public $oldStatus;
}
