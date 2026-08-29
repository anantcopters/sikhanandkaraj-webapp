<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Cache\Handlers\ApcuHandler;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

/**
 * Application cache configuration.
 *
 * PRODUCTION RULE
 * ===============
 *
 * The cache backend is deployment configuration rather than application
 * business logic.
 *
 * Current QA/Production architecture uses one application EC2 instance, so
 * file cache remains the appropriate default. A shared cache such as Redis is
 * only required if the application is later deployed across multiple
 * application instances.
 *
 * Environment variables allow that infrastructure change without requiring
 * application-code modification.
 */
class Cache extends BaseConfig
{
    /**
     * Preferred cache handler.
     *
     * Current default:
     *
     *     file
     *
     * Optional future values supported by CI4 include:
     *
     *     redis
     *     predis
     *     memcached
     *     apcu
     *
     * Example:
     *
     *     cache.handler = file
     */
    public string $handler;

    /**
     * Fallback handler when the primary cache backend is unavailable.
     *
     * "file" is preferable to "dummy" for a remote cache because temporary
     * Redis/Memcached failure should not silently disable useful application
     * caching.
     *
     * For the current file-cache deployment, dummy remains a safe fallback.
     */
    public string $backupHandler;

    /**
     * Namespace every application cache key.
     *
     * CloudFrontService already includes environment information inside its
     * own hashed key. The global prefix additionally protects every other CI4
     * cache consumer from collisions if a shared backend is introduced later.
     */
    public string $prefix;

    /**
     * Default TTL used by application cache consumers that do not explicitly
     * supply their own TTL.
     *
     * CloudFrontService supplies an explicit TTL and therefore does not depend
     * on this value.
     */
    public int $ttl;

    /**
     * PSR-6 reserved cache-key characters.
     */
    public string $reservedCharacters =
    '{}()/\\@:';

    /**
     * File-cache settings.
     *
     * writable/cache remains outside public web access.
     *
     * 0640 prevents world-readable cache files while allowing the application
     * process and its group to read them.
     *
     * @var array{storePath:string, mode:int}
     */
    public array $file = [
        'storePath' =>
        WRITEPATH . 'cache/',

        'mode' =>
        0640,
    ];

    /**
     * Memcached configuration.
     *
     * Kept because it is a CI4-supported handler even though the current
     * Sikhanandkaraj deployment does not require Memcached.
     *
     * @var array{
     *     host:string,
     *     port:int,
     *     weight:int,
     *     raw:bool
     * }
     */
    public array $memcached = [
        'host' =>
        '127.0.0.1',

        'port' =>
        11211,

        'weight' =>
        1,

        'raw' =>
        false,
    ];

    /**
     * Redis configuration.
     *
     * Redis is NOT required for the current single-instance deployment.
     *
     * These values simply preserve a clean future migration path if the
     * application is later horizontally scaled.
     *
     * @var array{
     *     host:string,
     *     password:string|null,
     *     port:int,
     *     timeout:int,
     *     async:bool,
     *     persistent:bool,
     *     database:int
     * }
     */
    public array $redis;

    /**
     * Cache handlers explicitly supported by the application/framework.
     *
     * @var array<string, class-string<CacheInterface>>
     */
    public array $validHandlers = [
        'apcu' =>
        ApcuHandler::class,

        'dummy' =>
        DummyHandler::class,

        'file' =>
        FileHandler::class,

        'memcached' =>
        MemcachedHandler::class,

        'predis' =>
        PredisHandler::class,

        'redis' =>
        RedisHandler::class,

        'wincache' =>
        WincacheHandler::class,
    ];

    /**
     * Page-cache query-string behaviour.
     *
     * Page caching is not being enabled as part of membership/search
     * optimization.
     *
     * Member pages are authorization-sensitive and must never be globally
     * cached merely to improve search performance.
     */
    public $cacheQueryString =
    false;

    /**
     * If PageCache is deliberately introduced elsewhere, only successful
     * responses should ever be eligible.
     *
     * This prevents temporary authentication/application errors from becoming
     * cached responses.
     *
     * @var list<int>
     */
    public array $cacheStatusCodes = [
        200,
    ];

    public function __construct()
    {
        parent::__construct();

        /*
         * Keep FILE as the deployment default.
         *
         * No Redis dependency is introduced by Membership-29/production
         * hardening.
         */
        $this->handler = $this->normalizeHandler(
            (string) env(
                'cache.handler',
                'file'
            ),
            'file'
        );

        /*
         * File cache failing generally means writable/cache itself is
         * unavailable. Dummy allows the request to continue without turning a
         * cache problem into application downtime.
         *
         * If Redis is introduced later, configure:
         *
         *     cache.backupHandler = file
         */
        $this->backupHandler =
            $this->normalizeHandler(
                (string) env(
                    'cache.backupHandler',
                    $this->handler === 'file'
                        ? 'dummy'
                        : 'file'
                ),
                $this->handler === 'file'
                    ? 'dummy'
                    : 'file'
            );

        /*
         * Use the application/environment identity as the default namespace.
         *
         * Examples:
         *
         *     sikhanandkaraj_development_
         *     sikhanandkaraj_qa_
         *     sikhanandkaraj_production_
         */
        $environmentName = strtolower(
            trim(
                (string) env(
                    'memberMedia.environmentName',
                    ENVIRONMENT
                )
            )
        );

        $environmentName = preg_replace(
            '/[^a-z0-9_-]+/',
            '_',
            $environmentName
        ) ?? '';

        if ($environmentName === '') {
            $environmentName =
                'application';
        }

        $defaultPrefix =
            'sikhanandkaraj_'
            . $environmentName
            . '_';

        $configuredPrefix = trim(
            (string) env(
                'cache.prefix',
                $defaultPrefix
            )
        );

        $this->prefix =
            $configuredPrefix !== ''
            ? $configuredPrefix
            : $defaultPrefix;

        $this->ttl = max(
            1,
            (int) env(
                'cache.ttl',
                60
            )
        );

        /*
         * Redis remains optional.
         *
         * Reading configuration does not establish a Redis connection unless
         * the redis/predis cache handler is selected.
         */
        $redisPassword = env(
            'cache.redis.password',
            null
        );

        $this->redis = [
            'host' =>
            trim(
                (string) env(
                    'cache.redis.host',
                    '127.0.0.1'
                )
            ),

            'password' =>
            $redisPassword !== null
                && trim(
                    (string) $redisPassword
                ) !== ''
                ? (string) $redisPassword
                : null,

            'port' =>
            max(
                1,
                (int) env(
                    'cache.redis.port',
                    6379
                )
            ),

            'timeout' =>
            max(
                0,
                (int) env(
                    'cache.redis.timeout',
                    0
                )
            ),

            'async' =>
            filter_var(
                env(
                    'cache.redis.async',
                    false
                ),
                FILTER_VALIDATE_BOOLEAN
            ),

            'persistent' =>
            filter_var(
                env(
                    'cache.redis.persistent',
                    false
                ),
                FILTER_VALIDATE_BOOLEAN
            ),

            'database' =>
            max(
                0,
                (int) env(
                    'cache.redis.database',
                    0
                )
            ),
        ];
    }

    /**
     * Accept only cache handlers supported by this configuration.
     *
     * Configuration errors therefore fail safely back to the deployment
     * default instead of allowing an arbitrary handler name.
     */
    private function normalizeHandler(
        string $handler,
        string $fallback
    ): string {
        $handler = strtolower(
            trim(
                $handler
            )
        );

        if (
            $handler !== ''
            && isset(
                $this->validHandlers[$handler]
            )
        ) {
            return $handler;
        }

        return $fallback;
    }
}
