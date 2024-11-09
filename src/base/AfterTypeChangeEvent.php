<?php
/**
 * Contains \deele\devkit\base\AfterTypeChangeEvent
 */

namespace deele\devkit\base;

use yii\base\Event;

/**
 * Class AfterTypeChangeEvent
 *
 * @property \yii\db\ActiveRecord|HasTypesTrait $sender
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\base
 */
class AfterTypeChangeEvent extends Event
{

    /**
     * @var int
     */
    public $newType;

    /**
     * @var int
     */
    public $oldType;
}
