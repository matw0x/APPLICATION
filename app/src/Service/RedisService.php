<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RedisService
{
    private \Redis $redisClient;

    public function __construct(
        #[Autowire(env: 'REDIS_DSN')]
        private readonly string $redisDSN,
    )
    {
        $this->redisClient = RedisAdapter::createConnection($this->redisDSN);
    }

    public function get(string $key): mixed
    {
        $response = null;
        if ($this->redisClient->ping()) {
            $response = $this->redisClient->hGetAll(key: $key);
        }

        $this->redisClient->close();

        return $response;
    }

    public function set(string $key, array $data): void
    {
        if ($this->redisClient->ping()) {
            $this->redisClient->hMSet($key, $data);
        }

        $this->redisClient->close();
    }

    public function expire(string $key, int $timeS): void
    {
        if ($this->redisClient->ping()) {
            $this->redisClient->expire($key, $timeS);
        }

        $this->redisClient->close();
    }

    public function delete(string $key): void
    {
        if ($this->redisClient->ping()) {
            $this->redisClient->del($key);
        }

        $this->redisClient->close();
    }
}