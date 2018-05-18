<?php
namespace fruitstudios\stripe\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $liveMode = false;

    public $testSecretKey = '';
    public $testPublishableKey = '';
    public $liveSecretKey = '';
    public $livePublishableKey = '';

    public $fee = 0;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['testSecretKey', 'testPublishableKey', 'liveSecretKey', 'livePublishableKey'], 'required'],
            ['fee', 'double', 'min' => 0, 'max' => 99.99],
            ['fee', 'default', 'value' => 0],
            ['liveMode', 'default', 'value' => false],
        ];
    }
}
