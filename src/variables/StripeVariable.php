<?php
namespace fruitstudios\stripe\variables;

use fruitstudios\stripe\Stripe;

use Stripe\Stripe as StripeApi;

class StripeVariable
{
    public function getSettings()
    {
        return Stripe::$plugin->getSettings();
    }


}
