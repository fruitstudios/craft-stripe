<?php
namespace fruitstudios\stripe;

use fruitstudios\stripe\models\Settings;
use fruitstudios\stripe\plugin\Routes as StripeRoutes;
use fruitstudios\stripe\plugin\Services as StripeServices;
use fruitstudios\stripe\web\twig\CraftVariableBehavior;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Fields;
use craft\services\UserPermissions;
use craft\helpers\UrlHelper;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

class Stripe extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;
    public static $settings;
    public static $devMode;
    public static $view;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';

    // Traits
    // =========================================================================

    use StripeServices;
    use StripeRoutes;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;
        self::$settings = Stripe::$plugin->getSettings();
        self::$devMode = Craft::$app->getConfig()->getGeneral()->devMode;
        self::$view = Craft::$app->getView();

        $this->name = Stripe::$settings->pluginNameOverride;
        $this->hasCpSection = Stripe::$settings->hasCpSectionOverride;

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
        $this->_registerVariables();

        Craft::info(Craft::t('stripe', '{name} plugin loaded', ['name' => $this->name]), __METHOD__);
    }

    public function beforeInstall(): bool
    {
        return true;
    }

    public function afterInstallPlugin(PluginEvent $event)
    {
        $isCpRequest = Craft::$app->getRequest()->isCpRequest;
        if ($event->plugin === $this && $isCpRequest)
        {
            Craft::$app->controller->redirect(UrlHelper::cpUrl('stripe/about'))->send();
        }
    }

    public function getSettingsResponse()
    {
        return Craft::$app->controller->redirect(UrlHelper::cpUrl('stripe/settings/plugin'));
    }

    public function getGitHubUrl(string $append = '')
    {
        return 'https://github.com/fruitstudios/craft-'.$this->handle.$append;
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {

             $event->permissions[Craft::t('stripe', 'Stripe')] = [
                'stripe-updatePluginSettings' => ['label' => Craft::t('stripe', 'Update Plugin Settings')],
                'stripe-updateStripeSettings' => ['label' => Craft::t('stripe', 'Update Stripe Settings')],
            ];

        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->attachBehavior('stripe', CraftVariableBehavior::class);
        });
    }

}
