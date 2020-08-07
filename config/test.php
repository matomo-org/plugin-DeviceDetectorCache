<?php

use Piwik\Plugins\DeviceDetectorCache\CachedEntry;

return [
    \Piwik\DeviceDetector\DeviceDetectorFactory::class => DI\decorate(function($previous) {
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT. '/tmp/devicecachetests/');

        return $previous;
    })
];