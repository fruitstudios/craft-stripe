<?php
namespace fruitstudios\stripe\models;

use Craft;
use craft\base\Model;

class ConnectedAccount extends Model
{
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
}
