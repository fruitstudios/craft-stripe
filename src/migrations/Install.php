<?php
/**
 * stripe plugin for Craft CMS 3.x
 *
 * Follow, Favourite, Bookmark, Like & Subscribe.
 *
 * @link      https://fruitstudios.co.uk
 * @copyright Copyright (c) 2018 Fruit Studios
 */

namespace fruitstudios\stripe\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    Fruit Studios
 * @package   Stripe
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    public $driver;

    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables())
        {
            $this->createIndexes();
            $this->addForeignKeys();
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%stripe}}');
        if($tableSchema === null)
        {
            $tablesCreated = true;
            $this->createTable(
                '{{%stripe_connectedaccounts}}',
                [
                    'id' => $this->primaryKey(),
                    'ownerId' => $this->integer()->notNull(),
                    'settings' => $this->text(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName('{{%stripe_connectedaccounts}}', 'ownerId', true),
            '{{%stripe_connectedaccounts}}',
            'ownerId',
            false
        );

        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    protected function addForeignKeys()
    {
         $this->addForeignKey(
            $this->db->getForeignKeyName('{{%stripe_connectedaccounts}}', 'ownerId'),
            '{{%stripe_connectedaccounts}}',
            'ownerId',
            '{{%elements}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%stripe_connectedaccounts}}');
    }
}
