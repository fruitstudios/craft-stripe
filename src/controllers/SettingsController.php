<?php
namespace fruitstudios\stripe\controllers;

use fruitstudios\stripe\Stripe;

use Craft;
use craft\web\Controller;
use craft\helpers\StringHelper;

use yii\web\Response;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPlugin()
    {
    	$this->requirePermission('stripe-updatePluginSettings');

        return $this->renderTemplate('stripe/settings/plugin', [
            'settings' => Stripe::$settings,
        ]);
    }

    public function actionStripe()
    {
    	$this->requirePermission('stripe-updateStripeSettings');

        return $this->renderTemplate('stripe/settings/stripe', [
            'settings' => Stripe::$settings,
        ]);
    }

    // craftcms/controllers/PluginsController - actionSaveSettings()
    public function actionSaveSettings()
    {
		$this->requirePostRequest();

        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
