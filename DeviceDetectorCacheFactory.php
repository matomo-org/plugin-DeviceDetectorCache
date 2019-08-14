<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use Piwik\DeviceDetector\DeviceDetectorFactory;

class DeviceDetectorCacheFactory extends DeviceDetectorFactory
{
    protected function getDeviceDetectionInfo($userAgent)
    {
        if (DeviceDetectorCacheEntry::isCached($userAgent)) {
            return new DeviceDetectorCacheEntry($userAgent);
        }

        return parent::getDeviceDetectionInfo($userAgent);
    }
}