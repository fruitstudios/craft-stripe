<?php
namespace fruitstudios\stripe\services;

use fruitstudios\stripe\Stripe;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\records\Element as ElementRecord;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\MatrixBlock;
use craft\models\Site;
use craft\helpers\ArrayHelper;
use craft\db\Query;

class Lists extends Component
{
    // Constants
    // =========================================================================

    const FOLLOW_LIST_HANDLE = 'follow';
    const STAR_LIST_HANDLE = 'star';
    const BOOKMARK_LIST_HANDLE = 'bookmark';
    const LIKE_LIST_HANDLE = 'like';
    const FAVOURITE_LIST_HANDLE = 'favourite';


    // Public Methods
    // =========================================================================

    public function isOnList(array $params)
    {
        $list = $this->_getList($params);
        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        if(!$list || !$owner || !$element || !$site)
        {
            return false;
        }

        $criteria = [
            'list' => $list,
            'ownerId' => $owner->id,
            'elementId' => $element->id,
        ];

        return Stripe::$plugin->subscriptions->getSubscription($criteria);
    }

    public function getSubscriptions($paramsOrList)
    {
        $list = $this->_getList($paramsOrList);
        $owner = $this->_getOwner($paramsOrList);
        $site = $this->_getSite($paramsOrList);

        if(!$list || !$owner || !$site)
        {
            return [];
        }

        $criteria = [
            'ownerId' => $owner->id,
            'siteId' => $site->id,
            'list' => $list
        ];

        return Stripe::$plugin->subscriptions->getSubscriptions($criteria);
    }

    public function getOwnerIds($params)
    {
        $list = $this->_getList($params);
        $element = $this->_getElement($params);
        $site = $this->_getSite($params);

        if(!$list || !$element || !$site)
        {
            return [];
        }

        $criteria = [
            'elementId' => $element->id,
            'siteId' => $site->id,
            'list' => $list
        ];

        return Stripe::$plugin->subscriptions->getSubscriptionsColumn($criteria, 'ownerId');
    }

    public function getOwners($params)
    {
        $ownerIds = $this->getOwnerIds($params);

        $query = $this->_getElementQuery(User::class, ($params['criteria'] ?? []));
        return $query
            ->id($ownerIds)
            ->all();
    }

    public function getElementIds($params)
    {
        $list = $this->_getList($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        if(!$list || !$owner || !$site)
        {
            return [];
        }

        $criteria = [
            'ownerId' => $owner->id,
            'list' => $list,
            'siteId' => $site->id,
        ];

        return Stripe::$plugin->subscriptions->getSubscriptionsColumn($criteria, 'elementId');
    }

    public function getElements($params)
    {
        $elementIds = $this->getElementIds($params);
        if(!$elementIds)
        {
            return [];
        }

        // Get craft element rows
        $type = $params['type'] ?? false;
        if($type)
        {
            $elements = (new Query())
                ->select(['id', 'type'])
                ->from([ElementRecord::tableName()])
                ->where([
                    'id' => $elementIds,
                    'type' => $type
                ])
                ->all();

            return $this->_getElementQuery($type, $params['criteria'] ?? [])
                ->id($elementIds)
                ->all();
        }
        else
        {
            // TODO: Is this over kill, is it even needed???
            $elementsToReturn = $elementIds;

            $elements = (new Query())
                ->select(['id', 'type'])
                ->from([ElementRecord::tableName()])
                ->where([
                    'id' => $elementIds,
                ])
                ->all();

            $elementIdsByType = [];
            foreach ($elements as $element)
            {
                $elementIdsByType[$element['type']][] = $element['id'];
            }

            foreach ($elementIdsByType as $elementType => $ids)
            {
                $criteria = ['id' => $ids];
                $elements = $this->_getElementQuery($elementType, $criteria)->all();

                foreach ($elements as $element)
                {
                    $key = array_search($element->id, $elementIds);
                    $elementsToReturn[$key] = $element;
                }
            }

            return $elementsToReturn;
        }
    }

    public function getEntries($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Entry::class
        ]);

        return $this->getElements($params);
    }

    public function getUsers($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => User::class
        ]);

        return $this->getElements($params);
    }

    public function getTags($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Tag::class
        ]);

        return $this->getElements($params);
    }

    public function getCategories($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => Category::class
        ]);

        return $this->getElements($params);
    }

    public function getMatrixBlocks($paramsOrList)
    {
        $params = $this->_convertToParamsArray($paramsOrList, 'list', [
            'type' => MatrixBlock::class
        ]);

        return $this->getElements($params);
    }

    // Add / Remove
    // =========================================================================

    public function addToList($params)
    {
        $list = $this->_getList($params);
        if(!$list)
        {
            return false;
        }

        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        // Create Subscription
        $subscription = Stripe::$plugin->subscriptions->createSubscription([
            'list' => $list,
            'ownerId' => $owner->id ?? null,
            'elementId' => $element->id ?? null,
            'siteId' => $site->id ?? null,
        ]);

        // Save Subscription
        return Stripe::$plugin->subscriptions->saveSubscription($subscription);
    }

    public function removeFromList($params)
    {
        $list = $this->_getList($params);
        if(!$list)
        {
            return false;
        }

        $element = $this->_getElement($params);
        $owner = $this->_getOwner($params);
        $site = $this->_getSite($params);

        // Subscription
        $subscription = Stripe::$plugin->subscriptions->getSubscription([
            'list' => $list,
            'ownerId' => $owner->id ?? null,
            'elementId' => $element->id ?? null,
            'siteId' => $site->id ?? null,
        ]);

        if (!$subscription)
        {
            return true;
        }

        // Delete Subscription
        return Stripe::$plugin->subscriptions->deleteSubscription($subscription->id);
    }


    // Favourite
    // =========================================================================

    public function favourite($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->addToList($params);
    }

    public function unFavourite($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->removeFromList($params);
    }

    public function isFavourited($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function getFavourites($paramsOrOwner)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->getSubscriptions($params);
    }

    public function getFavouritedElements($paramsOrOwner)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FAVOURITE_LIST_HANDLE
        ]);
        return $this->getElements($params);
    }


    // Favorite (US Spelling)
    // =========================================================================

    public function favorite($paramsOrElement)
    {
        return $this->favourite($paramsOrElement);
    }

    public function unFavorite($paramsOrElement)
    {
        return $this->unFavourite($paramsOrElement);
    }

    public function isFavorited($paramsOrElement)
    {
        return $this->isFavourited($paramsOrElement);
    }

    public function getFavorites($paramsOrOwner)
    {
        return $this->getFavourites($paramsOrOwner);
    }

    public function getFavoritedElements($paramsOrOwner)
    {
        return $this->getFavouritedElements($paramsOrOwner);
    }


    // Like
    // =========================================================================

    public function like($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->addToList($params);
    }

    public function unLike($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->removeFromList($params);
    }

    public function isLiked($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function getLikes($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->getSubscriptions($params);
    }

    public function getLikedElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::LIKE_LIST_HANDLE
        ]);
        return $this->getElements($params);
    }


    // Follow
    // =========================================================================

    public function follow($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->addToList($params);
    }

    public function unFollow($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->removeFromList($params);
    }

    public function isFollowing($paramsOrUserElement)
    {
        $params = $this->_convertToParamsArray($paramsOrUserElement, 'element', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);
        return $this->isOnList($params);
    }

    public function isFollower($paramsOrOwner)
    {
        // Use the supplied element, which should be a user element or grab the current user to check against
        $element = $paramsOrOwner['element'] ?? Craft::$app->getUser()->getIdentity();

        // Element supplied
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FOLLOW_LIST_HANDLE,
            'element' => $element
        ]);

        return $this->isOnList($params);
    }

    public function isFriend($paramsOrUserElement)
    {
        return $this->isFollowing($paramsOrUserElement) && $this->isFollower($paramsOrUserElement);
    }

    public function getFollowing($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);

        $elementIds = $this->getElementIds($params);

        $query = $this->_getElementQuery(User::class, ($paramsOrOwner['criteria'] ?? []));
        return $query
            ->id($elementIds)
            ->all();
    }

    public function getFollowers($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);

        $ownerIds = $this->getOwnerIds($params);

        $query = $this->_getElementQuery(User::class, ($paramsOrOwner['criteria'] ?? []));
        return $query
            ->id($ownerIds)
            ->all();
    }

    public function getFriends($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::FOLLOW_LIST_HANDLE
        ]);

        $ownerIds = $this->getOwnerIds($params);
        $elementIds = $this->getElementIds($params);

        $query = $this->_getElementQuery(User::class, ($paramsOrOwner['criteria'] ?? []));
        return $query
            ->id(array_intersect($ownerIds, $elementIds))
            ->all();
    }


    // Star
    // =========================================================================

    public function star($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->addToList($params);
    }

    public function unStar($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->removeFromList($params);
    }

    public function isStared($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->isOnList($params);
    }

    public function getStars($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->getSubscriptions($params);
    }

    public function getStarredElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::STAR_LIST_HANDLE
        ]);

        return $this->getElements($params);
    }


    // Bookmark
    // =========================================================================

    public function bookmark($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->addToList($params);
    }

    public function unBookmark($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->removeFromList($params);
    }

    public function isBookmarked($paramsOrElement)
    {
        $params = $this->_convertToParamsArray($paramsOrElement, 'element', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->isOnList($params);
    }

    public function getBookmarks($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->getSubscriptions($params);
    }

    public function getBookmarkedElements($paramsOrOwner = null)
    {
        $params = $this->_convertToParamsArray($paramsOrOwner, 'owner', [
            'list' => self::BOOKMARK_LIST_HANDLE
        ]);

        return $this->getElements($params);
    }

    // Private Methods
    // =========================================================================

    private function _convertToParamsArray($value, string $key, array $extend = [])
    {
        $params = is_array($value) ? $value : [$key => $value];
        return array_merge($params, $extend);
    }

    private function _getList($paramsOrList = null)
    {
        return is_string($paramsOrList) ? $paramsOrList : ($paramsOrList['list'] ?? false);
    }

    private function _getOwner($paramsOrOwner = null)
    {

        $ownerOrOwnerId = false;
        if($paramsOrOwner)
        {
            $ownerOrOwnerId = is_array($paramsOrOwner) ? ($paramsOrOwner['owner'] ?? false) : $paramsOrOwner;
        }

        $owner = $ownerOrOwnerId ? $ownerOrOwnerId : Craft::$app->getUser()->getIdentity();
        if($owner instanceof User)
        {
            return $owner;
        }

        return $ownerOrOwnerId ? Craft::$app->getUsers()->getUserById((int) $ownerOrOwnerId) : false;
    }

    private function _getElement($paramsOrElement = null)
    {
        $elementOrElementId = false;
        if($paramsOrElement)
        {
            $elementOrElementId = is_array($paramsOrElement) ? ($paramsOrElement['element'] ?? false) : $paramsOrElement;
        }

        if($elementOrElementId instanceof Element)
        {
            return $elementOrElementId;
        }

        return $elementOrElementId ? Craft::$app->getElements()->getElementById((int) $elementOrElementId) : false;
    }

    private function _getSite($paramsOrSite = null)
    {
        $siteOrSiteId = false;
        if($paramsOrSite)
        {
            $siteOrSiteId = is_array($paramsOrSite) ? ($paramsOrSite['site'] ?? false) : $paramsOrSite;
        }

        $site = $siteOrSiteId ? $siteOrSiteId : Craft::$app->getSites()->getCurrentSite();
        if($site instanceof Site)
        {
            return $site;
        }

        return $siteOrSiteId ? Craft::$app->getSites()->getSiteById((int) $siteOrSiteId) : false;
    }

    private function _getElementQuery($elementType, array $criteria): ElementQueryInterface
    {
        $query = $elementType::find();
        Craft::configure($query, $criteria);
        return $query;
    }
}
