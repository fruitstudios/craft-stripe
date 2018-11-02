<?php
namespace fruitstudios\stripe\controllers;

use Craft;
use craft\web\Controller;

class BaseController extends Controller
{

    // Protected Methods
    // =========================================================================

    protected function handleSuccessfulResponse(array $response = [])
    {
        $response['success'] = true;
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson($response);
        }

        $redirect = $response['redirect'] ?? null;
        if($redirect)
        {
            return $this->redirect($redirect);
        }

        Craft::$app->getUrlManager()->setRouteParams(
            $this->_buildRouteParams($response)
        );

        return null;
    }

    protected function handleFailedResponse(array $response = [])
    {
        $response['success'] = false;
        if (Craft::$app->getRequest()->getAcceptsJson())
        {
            return $this->asJson($response);
        }

        Craft::$app->getUrlManager()->setRouteParams(
            $this->_buildRouteParams($response)
        );

        return null;
    }


    private function _buildRouteParams(array $response = [])
    {
        $handle = $response['handle'] ?? false;
        if($handle)
        {
            unset($response['handle'], $response['redirect']);
            $response = [
                $handle => $response
            ];
        }

        return [
            'stripe' => $response
        ];
    }
}
