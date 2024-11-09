<?php
/**
 * Contains \deele\devkit\cache\CachedByTagTrait
 */

namespace deele\devkit\cache;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\caching\DummyCache;

/**
 * Class CachedByTagTrait
 *
 * @property Cache $cache {@link CachedByTagTrait::getCache()}
 *
 * Remember to add event listeners to your `ActiveRecord::init()`:
 * ~~~
 * public function init()
 * {
 *     $this->listenForChangesToInvalidateCache();
 *     parent::init();
 * }
 * ~~~
 *
 * @property string $primaryKey
 *
 * @author Nils (Deele) <deele@tuta.io>
 *
 * @package deele\devkit\cache
 */
trait CachedByTagTrait
{

    /**
     * @return string|array
     */
    public static function getCacheConfig()
    {
        return 'cache';
    }

    /**
     * @return CacheInterface|object
     * @throws InvalidConfigException
     */
    public static function getCache(): object
    {
        $cacheConfig = static::getCacheConfig();
        if (is_string($cacheConfig)) {
            return Yii::$app->{$cacheConfig};
        }

        if (is_array($cacheConfig)) {
            return Yii::createObject($cacheConfig);
        }

        return Yii::createObject([
            'class' => DummyCache::class
        ]);
    }

    /**
     * All the data related to the type of specific entity is marked with same name and cleared only
     * when full cache cleaning is requested.
     *
     * @return string
     */
    public static function cacheTagName(): string
    {
        return static::class;
    }

    /**
     * Data from ID cache is cleared when specific entity is modified by ID.
     *
     * @param mixed $id
     *
     * @return string
     */
    public static function cacheTagNameForId($id): string
    {
        if (is_numeric($id)) {
            $id = (float) $id;
        } else {
            $id = Json::encode($id);
        }
        return static::cacheTagName() . '.' . $id;
    }

    /**
     * Data from common cache is cleared every time any specific ID cache is deleted.
     *
     * @return string
     */
    public static function commonCacheTagName(): string
    {
        return static::cacheTagName() . '.common';
    }

    /**
     * Invalidates all cached data related to this package
     */
    public static function invalidateAllCachedData(): void
    {
        static::clearCacheByTags(static::cacheTagName());
    }

    /**
     * Method called after cached data related to model with specific ID was invalidated
     *
     * @param mixed $id
     */
    public static function afterInvalidateCachedDataById($id): void
    {
    }

    /**
     * Invalidates cached data related to model with specific ID
     *
     * @param mixed $id
     */
    public static function invalidateCachedDataById($id): void
    {
        static::clearCommonCache([
            static::cacheTagNameForId($id)
        ]);
        static::afterInvalidateCachedDataById($id);
    }

    /**
     * Invalidates common cached data related to any model with specific ID
     *
     * @param array $dependencyTags
     */
    public static function clearCommonCache(array $dependencyTags = []): void
    {
        $dependencyTags[] = static::commonCacheTagName();
        static::clearCacheByTags($dependencyTags);
    }

    /**
     * Returns all caches component names that are used
     *
     * @return array
     * @throws InvalidConfigException
     */
    public static function caches(): array
    {
        return [
            static::getCache()
        ];
    }

    /**
     * Invalidates cached data by tags
     *
     * @param string|array $tags
     * @throws InvalidConfigException
     */
    public static function clearCacheByTags($tags): void
    {
        foreach (static::caches() as $cache) {

            /**
             * @var Cache|string $cache
             */
            if (is_string($cache)) {
                $cache = Yii::$app->get($cache);
            }
            if (is_object($cache)) {
                TagDependency::invalidate(
                    $cache,
                    $tags
                );
            }
        }
        if (YII_DEBUG) {
            Yii::debug(
                'Cache cleared by tags: ' .
                VarDumper::export($tags),
                'application.caching'
            );
        }
    }

    /**
     * Invalidates cached data related to this model
     */
    public function invalidateCachedData(): void
    {

        /**
         * @var CachedByTagTrait|ActiveRecord $this
         */
        static::invalidateCachedDataById($this->getPrimaryKey(true));
    }

    /**
     * @param mixed $key
     *
     * @return bool
     * @throws InvalidConfigException
     */
    public static function cacheExists($key): bool
    {
        return static::getCache()->exists(
            $key
        );
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     * @throws InvalidConfigException
     */
    public static function cacheGet($key)
    {
        $data = static::getCache()->get($key);
        if ($data !== false && YII_DEBUG) {
            Yii::debug(
                'Data served from cache: ' .
                VarDumper::export($key),
                'application.caching'
            );
        }

        return $data;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param int|null $duration (in seconds) of cached information. 2629743 seconds is one month.
     * @param null|array $dependencyTags
     *
     * @return bool
     */
    public function cacheSet($key, $value, ?int $duration = 2629743, ?array $dependencyTags = null): bool
    {
        return static::cacheSetDependsOnId($key, $value, $this->primaryKey, $duration, $dependencyTags);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param null|int $id
     * @param int|null $duration (in seconds) of cached information. 2629743 seconds is one month.
     * @param array $dependencyTags
     *
     * @return bool
     */
    public static function cacheSetDependsOnId(
        $key,
        $value,
        ?int $id = null,
        ?int $duration = 2629743,
        array $dependencyTags = []
    ): bool {
        if (!is_null($id)) {
            $dependencyTags[] = static::cacheTagNameForId($id);
        }

        return static::cacheCommon($key, $value, $duration, $dependencyTags);
    }

    /**
     * Caches data in common cache set
     *
     * @param mixed $key
     * @param mixed $value
     * @param int|null $duration (in seconds) of cached information. 2629743 seconds is one month.
     * @param null|array $dependencyTags
     *
     * @return bool
     * @throws InvalidConfigException
     */
    public static function cacheCommon($key, $value, ?int $duration = 2629743, ?array $dependencyTags = null): bool
    {
        $tags = [
            static::cacheTagName(),
            static::commonCacheTagName(),
        ];
        if (!is_null($dependencyTags)) {
            if (!is_array($dependencyTags)) {
                $dependencyTags = [$dependencyTags];
            }
            $tags = array_merge($tags, $dependencyTags);
        }
        if (YII_DEBUG) {
            Yii::debug(
                'Cached by key: ' .
                VarDumper::export($key) .
                "\nDepends on tags: " .
                VarDumper::export($tags),
                'application.caching'
            );
        }
        return static::getCache()->set(
            $key,
            $value,
            $duration,
            new TagDependency([
                'tags' => $tags
            ])
        );
    }

    /**
     * Attaches event listeners to object to listen for update and create events to invalidate cache
     */
    public function listenForChangesToInvalidateCache(): void
    {
        if ($this instanceof ActiveRecord) {

            /**
             * @var CachedByTagTrait|ActiveRecord $this
             */
            $this->on(
                $this::EVENT_AFTER_INSERT,
                [$this, 'clearCommonCacheByEvent']
            );
            $this->on(
                $this::EVENT_AFTER_UPDATE,
                [$this, 'invalidateCachedDataByEvent']
            );
            $this->on(
                $this::EVENT_AFTER_DELETE,
                [$this, 'invalidateCachedDataByEvent']
            );
        }
    }

    /**
     * Invalidates cached data related to sender of after-save event
     */
    public function invalidateCachedDataByEvent(): void
    {
        static::invalidateCachedDataById($this->primaryKey);
    }

    /**
     * Clears common cached data
     */
    public function clearCommonCacheByEvent(): void
    {
        static::clearCommonCache();
    }
}
