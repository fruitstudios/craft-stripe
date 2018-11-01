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
    // Constants
    // =========================================================================

    const STRIPE_FEE_PERCENT = 1.4;
    const STRIPE_FEE_FLAT = 200;

    // Public Methods
    // =========================================================================

    public function charge(array $charge, $connectedAccount = null)
    {
        $secretKey = Stripe::$settings->getSecretKey();
        if(!$secretKey)
        {
            return false;
        }

        StripeApi::setApiKey($secretKey);

        if($connectedAccount)
        {
            $charge['application_fee'] = $this->calculateApplicationFee($charge);

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

    public function calculateApplicationFee(array $charge)
    {
        if(isset($charge['application_fee']))
        {
            return $charge['application_fee'];
        }

        $feePercent = Stripe::$settings->fee;
        if($feePercent == 0)
        {
            return 0;
        }

        if(Stripe::$settings->absorbFees && $feePercent > self::STRIPE_FEE_PERCENT)
        {
            $feePercent = $feePercent - self::STRIPE_FEE_PERCENT;
        }

        $applicationFeeAmount = StripeHelper::getPercentageValue($charge['amount'], $feePercent);
        if(Stripe::$settings->absorbFees)
        {
            $applicationFeeAmount = $applicationFeeAmount - self::STRIPE_FEE_FLAT;
        }

        return $applicationFeeAmount > 0 ? $applicationFeeAmount : 0;
    }
}
