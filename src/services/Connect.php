<?php
namespace fruitstudios\stripe\services;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\records\ConnectedAccount as ConnectedAccountnRecord;
use fruitstudios\stripe\events\ConnectedAccountEvent;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;

use GuzzleHttp\Client;
use Stripe\Stripe as StripeApi;

class StripeService extends Component
{
    // Public Methods
    // =========================================================================

    public function createConnectedAccount($code, ElementInterface $owner)
    {
        $secretKey = Stripe::$plugin->getSettings()->getSecretKey();
        if(!$secretKey)
        {
            return false;
        }

        try {

            $client = new Client();
            $request = $client->post('https://connect.stripe.com/oauth/token', false, [
                'client_secret' => $secretKey,
                'code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            $response = $request->send();
            if($response && $response->isSuccessful())
            {
                return $this->_createConnectedAccount([
                    'ownerId' => $owner->id,
                    'settings' => $response->json(),
                ]);
            }
            return false;

        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getConnectedAccount(ElementInterface $owner)
    {
        $connectedAccountRecord = ConnectedAccountRecord::findOne($owner->id);
        return $this->_createConnectedAccount($connectedAccountRecord);
    }

    public function saveConnectedAccount(ConnectedAccount $connectedAccount)
    {
        if (!$connectedAccount->validate()) {
            Craft::info('Connected Account not saved due to validation error.', __METHOD__);
            return false;
        }

        $connectedAccountRecord = ConnectedAccountRecord::findOne($connectedAccount->ownerId);
        if(!$connectedAccountRecord)
        {
            $connectedAccountRecord = new ConnectedAccountRecord();
        }

        $connectedAccountRecord->setAttributes($connectedAccount->getAttributes(), false);
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
