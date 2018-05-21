<?php
namespace fruitstudios\stripe\controllers;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\services\Charges;

use Craft;
use craft\base\Element;
use craft\web\Controller;

use Stripe\Stripe as StripApi;

class ConnectController extends Controller
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
        $code = $request->getParam('code', false);
        $error = $request->getParam('error', false);
        $state = explode(',', $request->getParam('state', ''));

        $ownerId = (int) $state[0];
        $owner = $ownerId ? Craft::$app->getElements()->getElementById($ownerId) : Craft::$app->getUser()->getIdentity();
        if(!$owner instanceof Element)
        {
            $error = 'Could not verify the account owner';
        }

        if($error)
        {
            return $this->_handleFailedResponse([
                'error' => $error
            ],
            Stripe::$plugin->getSettings()->connectFailurePath);
        }

        $connectedAccount = Stripe::$plugin->connect->createConnectedAccount($code, $owner);
        if(!Stripe::$plugin->connect->saveConnectedAccount($connectedAccount))
        {
            return $this->_handleFailedResponse([
                'errors' => $connectedAccount->getErrors()
            ], Stripe::$plugin->getSettings()->connectFailurePath);
        }

        return $this->_handleSuccessfulResponse([
            'message' => 'Stripe Connected'
        ], Stripe::$plugin->getSettings()->connectSuccessPath);
    }

    public function actionDeAuthorization()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();

        $owner = (int) $request->getParam('ownerId', false);
        $owner = $ownerId ? Craft::$app->getElements()->getElementById($ownerId) : Craft::$app->getUser()->getIdentity();

        if(!Stripe::$plugin->connect->removeConnectedAccount($code, $owner))
        {
            return $this->_handleFailedResponse( [
                'errors' => $connectedAccount->getErrors()
            ], Stripe::$plugin->getSettings()->connectFailurePath);
        }

        return $this->_handleSuccessfulResponse([
            'message' => 'Stripe Disconnected'
        ], Stripe::$plugin->getSettings()->connectSuccessPath);
    }

    // Private Methods
    // =========================================================================

    private function _handleSuccessfulResponse(array $result = [], string $redirect = null)
    {
        $result['success'] = true;
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson($result);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'stripe' => $result
        ]);

        return $redirect ? $this->redirect($redirect) : $this->redirectToPostedUrl();
    }

    private function _handleFailedResponse(array $result = [], string $redirect = null)
    {
        $result['success'] = false;
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson($result);
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'stripe' => $result
        ]);

        if($redirect)
        {
            return $this->redirect($redirect);
        }

        return null;
    }

}
