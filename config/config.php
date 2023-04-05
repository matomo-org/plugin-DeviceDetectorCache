<?php

return [
    'DeviceDetectorCacheIgnoreUserAgentsWithLessThanXRequests' => 9,
    'DeviceDetectorCacheNumLinesToScan' => 5000000,
    \Piwik\DeviceDetector\DeviceDetectorFactory::class
        => Piwik\DI::autowire(\Piwik\Plugins\DeviceDetectorCache\Factory::class),
];
