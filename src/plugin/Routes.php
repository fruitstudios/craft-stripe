<?php
namespace fruitstudios\stripe\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait Routes
{
    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['stripe'] = ['template' => 'stripe/index'];
            $event->rules['stripe/settings/plugin'] = 'stripe/settings/plugin';
            $event->rules['stripe/settings/stripe'] = 'stripe/settings/stripe';
        });
    }
}
