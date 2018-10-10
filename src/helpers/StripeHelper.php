<?php
namespace fruitstudios\stripe\helpers;

class StripeHelper
{
    // -----------------------------------------
    // Stripe
    //

    public static function costWithCurrencyLabel($amount, $currency = 'gbp')
    {
        $label = false;

        if(static::isNonDecimalCurrency($currency))
        {
            $amount = round($amount);
        }

        switch ($currency)
        {
            case 'gbp':
                $label = '&pound;'.$amount;
                break;

            case 'cad':
            case 'aud':
            case 'usd':
            case 'nzd':
                $label = '$'.$amount;
                break;

            case 'eur':
                $label = '&euro;'.$amount;
                break;

            case 'jpy':
                $label = '&yen;'.$amount;
                break;

            default:
                $label = $amount.' '.strtoupper($currency);
                break;
        }
        return $label;
    }

    public static function convertToStripeAmount($amount, $currency = 'gbp')
    {
        if(static::isNonDecimalCurrency($currency))
        {
            return round($amount);
        }
        else
        {
            return (int) ($amount * 100);
        }
    }

    public static function convertFromStripeAmount($amount, $currency = 'gbp')
    {
        if(static::isNonDecimalCurrency($currency))
        {
            return (int) $amount;
        }
        else
        {
            return (int) $amount / 100;
        }
    }

    public static function isNonDecimalCurrency(string $currency)
    {
        switch($currency)
        {
            case 'bif':
            case 'clp':
            case 'djf':
            case 'gnf':
            case 'jpy':
            case 'kmf':
            case 'krw':
            case 'mga':
            case 'pyg':
            case 'rwf':
            case 'vnd':
            case 'vuv':
            case 'xaf':
            case 'xof':
            case 'xpf':
                return true;
                break;

            default:
                return false;
                break;
        }
    }

    // -----------------------------------------
    // Number
    //

    public static function getPercentageValue($value, $percent, $round = true)
    {
        $calculated = $value * ($percent / 100);
        return $round ? round($calculated, 0) : $calculated;
    }


    public static function getPercentageDifference($base, $value)
    {
        $percentage = ( 1 - ( $value / $base ) ) * 100;
        return round($percentage, 2).'%';
    }




}
