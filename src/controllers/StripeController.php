<?php
namespace fruitstudios\stripe\controllers;

use fruitstudios\stripe\Stripe;
use fruitstudios\stripe\models\ConnectedAccount;
use fruitstudios\stripe\services\Charges;

use Craft;
use craft\web\Controller;

class StripeController extends Controller
{

    // Protected Properties
    // =========================================================================

    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    public function actionCharge()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        // $owner = $this->_getOwner();
        // $element = $this->_getElement();
        // $list = $this->_getList();
        // $site = $this->_getSite();

        // // Create Subscription
        // $subscription = Stripe::$plugin->subscriptions->createSubscription([
        //     'ownerId' => $owner->id ?? null,
        //     'elementId' => $element->id ?? null,
        //     'list' => $list,
        //     'siteId' => $site->id ?? null,
        // ]);

        // // Save Subscription
        // if (!Stripe::$plugin->subscriptions->saveSubscription($subscription))
        // {
        //     return $this->_handleFailedResponse($subscription);
        // }
        // return $this->_handleSuccessfulResponse($subscription);
    }

    // Private Methods
    // =========================================================================

    private function _handleSuccessfulResponse($subscription = null, array $result = [])
    {
        $result['success'] = true;

        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            if($subscription instanceof Subscription)
            {
                $result['subscription'] = [
                    'id' => $subscription->id,
                    'ownerId' => $subscription->ownerId,
                    'elementId' => $subscription->elementId,
                    'list' => $subscription->list,
                    'siteId' => $subscription->siteId
                ];
            }
            return $this->asJson($result);
        }

        $result['subscription'] = $subscription;
        Craft::$app->getUrlManager()->setRouteParams([
            'stripe' => $result
        ]);

        return $this->redirectToPostedUrl();
    }

    private function _handleFailedResponse($subscription = null, array $result = [])
    {
        $result['success'] = false;

        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            if($subscription instanceof Subscription)
            {
                $result['errors'] = $subscription->getErrors();
            }
            return $this->asJson($result);
        }

        $result['subscription'] = $subscription;
        Craft::$app->getUrlManager()->setRouteParams([
            'stripe' => $result
        ]);

        return null;
    }

}
