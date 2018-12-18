<?php
namespace fruitstudios\stripe\web\twig;

use fruitstudios\stripe\Stripe;

use Craft;
use yii\base\Behavior;

class CraftVariableBehavior extends Behavior
{
    public $stripe;

    public function init()
    {
        parent::init();
        // Point `craft.stripe` to the craft\stripe\Plugin instance
        $this->stripe = Stripe::getInstance();
    }

}
