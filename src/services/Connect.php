<?php
namespace fruitstudios\stripe\services;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\records\ConnectedAccount as ConnectedAccountRecord;

use fruitstudios\stripe\events\ConnectedAccountEvent;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Stripe\Stripe as StripeApi;
use Stripe\Account as StripeAccount;

class Connect extends Component
{

    // Constants
    // =========================================================================

    const EVENT_STRIPE_ACCOUNT_CONNECTED = 'stripeAccountConnected';
    const EVENT_STRIPE_ACCOUNT_DISCONNECTED = 'stripeAccountDisconnected';


    // Public Methods
    // =========================================================================

    public function createConnectedAccount($attributes = [])
    {
        return $this->_createConnectedAccount($attributes);
    }

    public function getConnectedAccount($idOrParams)
    {
        $connectedAccountRecord = ConnectedAccountRecord::findOne($idOrParams);
        return $this->_createConnectedAccount($connectedAccountRecord);
    }

    public function getConnectedAccountByOwner(ElementInterface $owner)
    {
        $connectedAccountRecord = ConnectedAccountRecord::findOne([
            'ownerId' => $owner->id
        ]);
        return $this->_createConnectedAccount($connectedAccountRecord);
    }

    public function getConnectedAccountByOwnerId(int $ownerId)
    {
        $owner = Craft::$app->getElements()->getElementById($ownerId);
        if(!$owner)
        {
            return false;
        }
        return $this->getConnectedAccountByOwner($owner);
    }

    public function saveConnectedAccount(ConnectedAccount $connectedAccount)
    {
        if (!$connectedAccount->validate()) {
            Craft::info('Connected Account not saved due to validation error.', __METHOD__);
            return false;
        }

        $connectedAccountRecord = ConnectedAccountRecord::findOne([
            'ownerId' => $connectedAccount->ownerId
        ]);
        if($connectedAccountRecord)
        {
            $connectedAccountRecord->settings = $connectedAccount->settings;
        }
        else
        {
            $connectedAccountRecord = new ConnectedAccountRecord();
            $connectedAccountRecord->setAttributes($connectedAccount->getAttributes(), false);
        }

        if(!$connectedAccountRecord->save(false))
        {
            return false;
        }

        $connectedAccountModel = $this->_createConnectedAccount($connectedAccountRecord);

        $this->trigger(self::EVENT_STRIPE_ACCOUNT_CONNECTED, new ConnectedAccountEvent([
            'connectedAccount' => $connectedAccountModel
        ]));

        return true;
    }

    public function deleteConnectedAccount(ConnectedAccount $connectedAccount)
    {
        $connectedAccountRecord = ConnectedAccountRecord::findOne($connectedAccount->id);
        if($connectedAccountRecord)
        {
            try {

                $connectedAccountRecord->delete();

                $this->trigger(self::EVENT_STRIPE_ACCOUNT_DISCONNECTED, new ConnectedAccountEvent([
                    'connectedAccount' => $connectedAccount
                ]));

                return true;

            } catch (\StaleObjectException $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return false;
            } catch (\Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return false;
            } catch (\Throwable $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return false;
            }
        }

    }

    // Private Methods
    // =========================================================================

    private function _createConnectedAccount($config = null)
    {
        if (!$config) {
            return null;
        }

        if($config instanceof ConnectedAccount)
        {
            $config = $connectedAccountRecord->toArray([
                'id',
                'ownerId',
                'settings',
                'dateCreated'
            ]);
        }

        $connectedAccount = new ConnectedAccount($config);
        return $connectedAccount;
    }
}
