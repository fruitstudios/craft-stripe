<?php
namespace fruitstudios\stripe\variables;

use fruitstudios\stripe\Stripe;

use craft\base\ElementInterface;

class StripeVariable
{
    public function getSettings()
    {
        return Stripe::$plugin->getSettings();
    }

    public function getConnectedAccount(ElementInterface $element)
    {
        return Stripe::$plugin->connect->getConnectedAccountByOwner($element);
    }


}
