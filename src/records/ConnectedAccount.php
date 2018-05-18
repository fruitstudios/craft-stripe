<?php
namespace fruitstudios\stripe\records;

use craft\db\ActiveRecord;

class ConnectedAccount extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    public static function tableName()
    {
        return '{{%stripe_connectedaccounts}}';
    }
}
