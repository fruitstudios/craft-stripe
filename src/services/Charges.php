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

        $applicationFeeAmount = StripeHelper::getPercentageValue($charge['amount'], $feePercent);
        var_dump(self::STRIPE_FEE_FLAT);
        var_dump(self::STRIPE_FEE_PERCENT);
        var_dump($applicationFeeAmount);
        if(Stripe::$settings->absorbFees)
        {
            $stripeFeeAmount = self::STRIPE_FEE_FLAT + StripeHelper::getPercentageValue($charge['amount'], self::STRIPE_FEE_PERCENT);
            if($applicationFeeAmount >= $stripeFeeAmount)
            {
                $applicationFeeAmount = $applicationFeeAmount - $stripeFeeAmount;
            }
        }

        var_dump($applicationFeeAmount);
        var_dump(Stripe::$settings->absorbFees);
        var_dump($applicationFeeAmount > 0 ? $applicationFeeAmount : 0);

        die;
        return $applicationFeeAmount > 0 ? $applicationFeeAmount : 0;
    }
}
