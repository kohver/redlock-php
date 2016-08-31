<?php

use Predis\Client;
use RedLock\RedLock;

require_once __DIR__ . '/../src/RedLock.php';
require_once __DIR__ . '/../vendor/autoload.php';

$RedLock = new RedLock([
    new Client(['host' => '127.0.0.1', 'port' => 6379, 'timeout' => 0.01]),
    new Client(['host' => '127.0.0.1', 'port' => 6380, 'timeout' => 0.01]),
    new Client(['host' => '127.0.0.1', 'port' => 6381, 'timeout' => 0.01]),
]);

while (true) {
    $lock = $RedLock->lock('test', 10000);

    if ($lock) {
        print_r($lock);
    } else {
        print "Lock not acquired\n";
    }
}
