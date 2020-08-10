<?php
return [
    'DeviceDetectorCacheIgnoreUserAgentsWithLessThanXRequests' => 9,
    'DeviceDetectorCacheNumLinesToScan' => 5000000,
    \Piwik\DeviceDetector\DeviceDetectorFactory::class
        => DI\object(\Piwik\Plugins\DeviceDetectorCache\Factory::class),
];