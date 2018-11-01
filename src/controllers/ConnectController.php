<?php
namespace fruitstudios\stripe\controllers;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\controllers\BaseController;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\services\Charges;

use Craft;
use craft\base\Element;
use craft\web\Controller;
use craft\helpers\Json;

class ConnectController extends BaseController
{

    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    public function actionAuthorization()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $state = explode(',', $request->getParam('state', ''));
        $code = $request->getParam('code', false);

        $redirect = $state[1] ?? Stripe::$plugin->getSettings()->connectAccountPath;

        $error = $request->getParam('error', false);
        if($error)
        {
            $errorDescription = $request->getParam('error_description', null);
            return $this->handleFailedResponse([
                'error' => $errorDescription ?? $error,
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        $ownerId = $state[0] ?? false;
        $owner = $ownerId ? Craft::$app->getElements()->getElementById($ownerId) : Craft::$app->getUser()->getIdentity();
        if(!$owner instanceof Element)
        {
            return $this->handleFailedResponse([
                'error' => 'Could not verify the account owner',
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        try {

            $secretKey = Stripe::$plugin->getSettings()->getSecretKey();

            $client = Craft::createGuzzleClient([
                'base_uri' => 'https://connect.stripe.com/'
            ]);

            $response = $client->request('POST', 'oauth/token', [
                'form_params' => [
                    'client_secret' => $secretKey,
                    'code' => $code,
                    'grant_type' => 'authorization_code'
                ]
            ]);

            $connectedAccount = Stripe::$plugin->connect->createConnectedAccount([
                'ownerId' => $owner->id,
                'settings' => Json::decode((string)$response->getBody()),
            ]);

        } catch (\Exception $e) {

            return $this->handleFailedResponse([
                'errors' => $e->getMessage(),
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);

        }

        $saved = Stripe::$plugin->connect->saveConnectedAccount($connectedAccount);
        if(!$saved)
        {
            return $this->handleFailedResponse([
                'errors' => $connectedAccount->getErrors(),
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        return $this->handleSuccessfulResponse([
            'message' => 'Stripe Connected',
            'redirect' => $redirect,
            'handle' => 'connect'
        ]);
    }

    public function actionDeAuthorization()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $redirect = $request->getBodyParam('redirect', (Stripe::$plugin->getSettings()->connectAccountPath ?? '/'));

        $ownerId = (int) $request->getParam('ownerId', false);
        $connectedAccount = $ownerId ? Stripe::$plugin->connect->getConnectedAccountByOwnerId($ownerId) : false;

        // TODO: Add some permissions here to determine is the current user can remove other peoples accounts
        //     : Currently using a temp check against current user (owner element will always be a user here)
        if($currentUser->id != $ownerId)
        {
            return $this->handleFailedResponse([
                'error' => 'Permission error',
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        if(!$connectedAccount)
        {
            return $this->handleFailedResponse([
                'error' => 'Invalid connected account',
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        if(!Stripe::$plugin->connect->deleteConnectedAccount($connectedAccount->id))
        {
            return $this->handleFailedResponse([
                'error' => 'Could not disconnect account',
                'redirect' => $redirect,
                'handle' => 'connect'
            ]);
        }

        return $this->handleSuccessfulResponse([
            'message' => 'Stripe Disconnected',
            'redirect' => $redirect,
            'handle' => 'connect'
        ]);
    }
}
