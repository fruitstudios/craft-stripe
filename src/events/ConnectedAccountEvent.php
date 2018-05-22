<?php
namespace fruitstudios\stripe\events;

use yii\base\Event;

class ConnectedAccountEvent extends Event
{
    public $connectedAccount;
}
