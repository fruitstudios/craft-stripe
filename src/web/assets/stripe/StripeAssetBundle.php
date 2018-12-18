<?php
namespace fruitstudios\stripe\web\assets\stripe;

use Craft;

use yii\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class StripeAssetBundle extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@fruitstudios/stripe/web/assets/stripe/build";

        $this->depends = [];
        $this->css = [
            'css/cp.css',
        ];

        parent::init();
    }
}
