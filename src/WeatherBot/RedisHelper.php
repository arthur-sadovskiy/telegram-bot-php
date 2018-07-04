<?php

namespace WeatherBot;

class RedisHelper
{
    private const REDIS_KEY_PREFIX = 'wheatherin_';

    /**
     * @var null|\Redis
     */
    private $redisClient;

    /**
     * @param array $config
     *
     * @return $this
     */
    public function createRedisClient(array $config): RedisHelper
    {
        if (!empty($config['host']) && !empty($config['port']) && \extension_loaded('redis')) {
            $client = new \Redis();
            $client->connect(
                $config['host'],
                $config['port']
            );
            $this->redisClient = $client;
        }

        return $this;
    }

    /**
     * @return null|\Redis
     */
    public function getRedisClient(): ?\Redis
    {
        return $this->redisClient;
    }

    /**
     * @param string $reply
     *
     * @return string
     */
    public function prependCacheSign(string $reply): string
    {
        return "\u{1F4BE}" . '  ' . $reply; // floppy disk emoji
    }

    /**
     * @param int $cityId
     * @param bool $isDetailed
     *
     * @return string
     */
    public function getRedisKey(int $cityId, bool $isDetailed): string
    {
        return self::REDIS_KEY_PREFIX . $cityId . '_' . (int) $isDetailed;
    }
}
