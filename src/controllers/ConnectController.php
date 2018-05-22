<?php
namespace fruitstudios\stripe\controllers;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\controllers\BaseController;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\services\Charges;

use Craft;
use craft\base\Element;
use craft\web\Controller;

use Stripe\Stripe as StripApi;

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

        $connectedAccount = Stripe::$plugin->connect->createConnectedAccount($code, $owner);
        if(!Stripe::$plugin->connect->saveConnectedAccount($connectedAccount))
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

        $owner = (int) $request->getParam('ownerId', false);
        $owner = $ownerId ? Craft::$app->getElements()->getElementById($ownerId) : Craft::$app->getUser()->getIdentity();

        $redirect = Stripe::$plugin->getSettings()->connectAccountPath;

        if(!Stripe::$plugin->connect->removeConnectedAccount($code, $owner))
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
}
