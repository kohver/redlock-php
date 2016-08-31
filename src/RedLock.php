<?php

namespace RedLock;

use Predis\Client as Predis;

class RedLock {
    private $retryDelay;
    private $retryCount;
    private $clockDriftFactor = 0.01;

    private $quorum;
    private $Instances = [];

    /**
     * @param Predis[] $Instances
     * @param int $retryDelay
     * @param int $retryCount
     */
    function __construct(array $Instances, $retryDelay = 200, $retryCount = 3) {
        $this->Instances  = $Instances;
        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
        $this->quorum     = min(count($Instances), (count($Instances) / 2 + 1));
    }

    /**
     * @param string $resource
     * @param int $ttl
     * @return Lock
     * @throws LockTimeoutException
     */
    public function lock($resource, $ttl) {
        $token = uniqid();
        $retry = $this->retryCount;

        do {
            $n = 0;

            $startTime = microtime(true) * 1000;

            foreach ($this->Instances as $Instance) {
                if ($this->lockInstance($Instance, $resource, $token, $ttl)) {
                    $n++;
                }
            }

            // Add 2 milliseconds to the drift to account for Redis expires
            // precision, which is 1 millisecond, plus 1 millisecond min drift
            // for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + 2;

            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;

            if ($n >= $this->quorum && $validityTime > 0) {
                return new Lock($validityTime, $resource, $token);
            } else {
                foreach ($this->Instances as $Instance) {
                    $this->unlockInstance($Instance, $resource, $token);
                }
            }

            // Wait a random delay before to retry in order to try to desynchronize multiple
            // clients trying to acquire the lock, for the same resource, at the same time.
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);

            $retry--;

        } while ($retry > 0);

        throw new LockTimeoutException();
    }

    /**
     * @param Lock $Lock
     */
    public function unlock(Lock $Lock) {
        $resource = $Lock->getResource();
        $token    = $Lock->getToken();

        foreach ($this->Instances as $Instance) {
            $this->unlockInstance($Instance, $resource, $token);
        }
    }

    /**
     * @param Predis $Instance
     * @param string $resource
     * @param string $token
     * @param int $ttl
     * @return bool
     */
    private function lockInstance(Predis $Instance, $resource, $token, $ttl) {
        return $Instance->set($resource, $token, 'PX', $ttl, 'NX');
    }

    /**
     * @param Predis $Instance
     * @param string $resource
     * @param string $token
     * @return mixed
     */
    private function unlockInstance(Predis $Instance, $resource, $token) {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        return $Instance->eval($script, 1, $resource, $token);
    }
}
