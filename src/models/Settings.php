<?php
namespace fruitstudios\stripe\models;

use craft\base\ElementInterface;
use craft\base\Model;
use craft\helpers\UrlHelper;

class Settings extends Model
{
    // Constants
    // =========================================================================

    const CONNECT_OAUTH_URL = 'https://connect.stripe.com/oauth/authorize';
    const AUTH_PATH = 'stripe/connect/authorization';
    const DEAUTH_PATH = 'stripe/connect/de-authorization';

    // Public Properties
    // =========================================================================

    public $pluginNameOverride = 'Stripe';
    public $hasCpSectionOverride = false;

    public $liveMode = false;
    public $testSecretKey;
    public $testPublishableKey;
    public $liveSecretKey;
    public $livePublishableKey;
    public $testConnectClientId;
    public $liveConnectClientId;
    public $connectAccountPath;
    public $fee = 0;
    public $absorbFees = false;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            ['pluginNameOverride', 'string'],
            ['pluginNameOverride', 'default', 'value' => 'Stripe'],
            ['hasCpSectionOverride', 'boolean'],
            ['hasCpSectionOverride', 'default', 'value' => false],
            [
                [
                    'testSecretKey',
                    'testPublishableKey',
                    'liveSecretKey',
                    'livePublishableKey',
                    'testConnectClientId',
                    'liveConnectClientId',
                    'connectAccountPath',
                ],
                'string'
            ],
            [['liveSecretKey', 'livePublishableKey'], 'required', 'when' => [$this, 'isLiveMode']],
            [['connectAccountPath'], 'required', 'when' => [$this, 'isConnectAvailable']],
            ['fee', 'double', 'min' => 0, 'max' => 99.99],
            ['fee', 'default', 'value' => 0],
            ['liveMode', 'boolean', 'trueValue' => 1, 'falseValue' => 0],
            [['liveMode', 'absorbFees'], 'default', 'value' => false],
        ];
    }

    public function isLiveMode()
    {
        return $this->liveMode;
    }

    public function isConnectAvailable()
    {
        return ($this->liveMode && $this->liveConnectClientId) || (!$this->liveMode && $this->testConnectClientId);
    }

    public function getKeys()
    {
        $secretKey = $this->getSecretKey();
        $publishableKey = $this->getPublishableKey();
        $connectClientId = $this->getConnectClientId();

        if(!$secretKey || !$publishableKey)
        {
            return false;
        }

        return [
            'secretKey' => $secretKey,
            'publishableKey' => $publishableKey,
            'connectClientId' => $connectClientId,
        ];
    }

    public function getSecretKey()
    {
        return $this->liveMode ? $this->liveSecretKey : $this->testSecretKey;
    }

    public function getPublishableKey()
    {
        return $this->liveMode ? $this->livePublishableKey : $this->testPublishableKey;
    }

    public function getConnectClientId()
    {
        return $this->liveMode ? $this->liveConnectClientId : $this->testConnectClientId;
    }

    public function getFeeString()
    {
        return (string) $this->fee.'% ('.($this->absorbFees ? 'Including' : 'Excluding').' Stripe Fees)';
    }

    public function getConnectOauthUrl(ElementInterface $owner, array $state = [])
    {
        $connectClientId = $this->getConnectClientId();
        if(!$connectClientId)
        {
            return '';
        }

        $state = array_merge([
            $owner->id
        ], $state);

        return UrlHelper::urlWithParams(self::CONNECT_OAUTH_URL, [
            'response_type' => 'code',
            'client_id' => $connectClientId,
            'scope' => 'read_write',
            'redirect_uri' => UrlHelper::actionUrl(self::AUTH_PATH, null, 'https'),
            'state' => implode(',', $state),
        ]);
    }


}
