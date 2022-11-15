<?php

use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Matomo\Dependencies\DI;

return [
    'DeviceDetectorCacheIgnoreUserAgentsWithLessThanXRequests' => 0,
    \Piwik\DeviceDetector\DeviceDetectorFactory::class => DI\decorate(function ($previous) {
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT . '/tmp/devicecachetests/');

        return $previous;
    })
];
