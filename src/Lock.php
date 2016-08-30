<?php

namespace RedLock;

class Lock {
    protected $validity;
    protected $resource;
    protected $token;

    /**
     * @param int $validity
     * @param string $resource
     * @param string $token
     */
    public function __construct(
        $validity,
        $resource,
        $token
    ) {
        $this->validity = $validity;
        $this->resource = $resource;
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getValidity() {
        return $this->validity;
    }

    /**
     * @return string
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getToken() {
        return $this->token;
    }
}
