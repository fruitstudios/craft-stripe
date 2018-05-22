<?php
namespace fruitstudios\stripe\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;

class ConnectedAccount extends Model
{
    // Private Properties
    // =========================================================================

    private $_owner;
    private $_settings;

    // Public Properties
    // =========================================================================

    public $id;
    public $ownerId;
    public $settings;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['ownerId'], 'integer'],
            [['ownerId', 'settings'], 'required'],
        ];
    }

    public function getOwner()
    {
        if(is_null($this->_owner))
        {
            $this->_owner = Craft::$app->getElements()->getElementById($this->ownerId);
        }
        return $this->_owner;
    }

    public function getSettings()
    {
        if(is_null($this->_settings))
        {
            $this->_settings = Json::decode($this->settings);
        }
        return $this->_settings;
    }

    public function getLiveMode()
    {
        $settings = $this->getSettings();
        return (bool) $settings['livemode'] ?? false;
    }

    public function getAccountId()
    {
        $settings = $this->getSettings();
        return $settings['stripe_user_id'] ?? '';
    }
}
