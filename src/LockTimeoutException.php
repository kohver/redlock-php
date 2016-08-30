<?php

namespace RedLock;

use Exception;

class LockTimeoutException extends Exception {
    public function __construct() {
        $this->message = 'Lock timeout.';
    }
}
