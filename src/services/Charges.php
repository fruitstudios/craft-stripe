<?php
namespace fruitstudios\stripe\services;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\helpers\StripeHelper;

use Craft;
use craft\base\Component;

use Stripe\Stripe as StripeApi;
use Stripe\Charge as StripeCharge;


class Charges extends Component
{

    // Public Methods
    // =========================================================================

    public function charge($charge, $connectedAccount = null)
    {
        $secretKey = Stripe::$plugin->getSettings()->getSecretKey();
        if(!$secretKey)
        {
            return false;
        }

        StripeApi::setApiKey($secretKey);

        if($connectedAccount)
        {
            $feePercent = Stripe::$plugin->getSettings()->fee ?? 0;
            $charge['application_fee'] = StripeHelper::getPercentageValue($charge['amount'], $feePercent);

            $payment = StripeCharge::create($charge, [
                'stripe_account' => $connectedAccount->getAccountId()
            ]);
        }
        else
        {
            $payment = StripeCharge::create($charge);
        }

        return $payment;

    }
}
