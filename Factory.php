<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use Piwik\DeviceDetector\DeviceDetectorFactory;

class Factory extends DeviceDetectorFactory
{
    protected function getDeviceDetectionInfo($userAgent)
    {
        $cache = CachedEntry::getCached($userAgent);
        if (!empty($cache)) {
            return new CachedEntry($userAgent, $cache);
        }

        return parent::getDeviceDetectionInfo($userAgent);
    }
}