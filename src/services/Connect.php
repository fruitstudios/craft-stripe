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

    public function deleteConnectedAccount($connectedAccountId)
    {
        $connectedAccountRecord = ConnectedAccountRecord::findOne($connectedAccountId);

        if($connectedAccountRecord) {
            try {

                $connectedAccountModel = $this->_createConnectedAccount($connectedAccountRecord);
                $connectedAccountRecord->delete();

                $this->trigger(self::EVENT_STRIPE_ACCOUNT_DISCONNECTED, new ConnectedAccountEvent([
                    'connectedAccount' => $connectedAccountModel
                ]));

            } catch (\StaleObjectException $e) {
                Craft::error($e->getMessage(), __METHOD__);
            } catch (\Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
            } catch (\Throwable $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }
        }

        return true;
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
