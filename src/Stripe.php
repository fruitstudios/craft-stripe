<?php
namespace fruitstudios\stripe;

use fruitstudios\stripe\models\Settings;
use fruitstudios\stripe\services\Charges;
use fruitstudios\stripe\services\Connect;
use fruitstudios\stripe\variables\StripeVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

class Stripe extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;
    public static $settings;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;
        self::$settings = Stripe::$plugin->getSettings();

        $this->setComponents([
            'charges' => Charges::class,
            'connect' => Connect::class,
        ]);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('stripe', StripeVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'stripe',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('stripe/settings', [
            'settings' => $this->getSettings()
        ]);
    }

}
