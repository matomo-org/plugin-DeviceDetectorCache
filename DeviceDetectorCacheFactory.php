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
    private $cache = array();

    public function makeInstance($userAgent)
    {
        if (isset($this->cache[$userAgent])) {
            return $this->cache[$userAgent];
        }
        if (DeviceDetectorCacheEntry::isCached($userAgent)) {
            $this->cache[$userAgent] = new DeviceDetectorCacheEntry($userAgent);
            return $this->cache[$userAgent];
        }

        return parent::makeInstance($userAgent);
    }
}