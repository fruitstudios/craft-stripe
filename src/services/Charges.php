<?php
namespace fruitstudios\stripe\services;

use fruitstudios\stripe\Stripe;

use Craft;
use craft\base\Component;

use Stripe\Stripe as StripeApi;

class Lists extends Component
{

    // Public Methods
    // =========================================================================

    public function makePayment($charge, $connectedAccount = null)
    {
        $secretKey = Stripe::$plugin->getSettings()->getSecretKey();
        if(!$secretKey)
        {
            return false;
        }

        try {

            StripeApi::setApiKey($secretKey);

            if($connectedAccount)
            {
                $payment = StripeApi\Charge::create($charge, [
                    'stripe_account' => $connectedAccount->getAccountId()
                ]);
            }
            else
            {
                $payment = StripeApi\Charge::create($charge);
            }

            return $payment;

        } catch (\Exception $e) {

            return false;

        }
    }
}
