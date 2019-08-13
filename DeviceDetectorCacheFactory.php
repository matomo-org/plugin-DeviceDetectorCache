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
    private $useFileCache = true;

    protected function getDeviceDetectionInfo($userAgent)
    {
        if ($this->useFileCache && DeviceDetectorCacheEntry::isCached($userAgent)) {
            return new DeviceDetectorCacheEntry($userAgent);
        } else {
            return parent::getDeviceDetectionInfo($userAgent);
        }
    }

    public function setUseFileCache($use)
    {
        $this->useFileCache = $use;
    }
}