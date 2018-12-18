<?php
namespace fruitstudios\stripe\plugin;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\services\Charges;
use fruitstudios\stripe\services\Connect;

use Craft;
use craft\base\ElementInterface;

trait Services
{
    // Public Methods
    // =========================================================================

    // public function getSettings()
    // {
    //     return Stripe::$settings;
    // }

    public function getCharges(): Charges
    {
        return $this->get('charges');
    }

    public function getConnect(): Connect
    {
        return $this->get('connect');
    }

    public function getConnectedAccount(ElementInterface $element)
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '{{ craft.stripe.getConnectedAccountByOwner() }} has been deprecated. Use {{ craft.stripe.connect.getConnectedAccountByOwner() }} instead.');
        return $this->getConnect()->getConnectedAccountByOwner($element);
    }

    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'charges' => Charges::class,
            'connect' => Connect::class,
        ]);
    }
}
