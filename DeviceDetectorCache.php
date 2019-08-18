<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use DeviceDetector\DeviceDetector;
use Piwik\DeviceDetector\DeviceDetectorFactory;

class DeviceDetectorCache extends \Piwik\Plugin
{

    public function isTrackerPlugin()
    {
        return true;
    }

    public static function writeToCache($userAgentStr, DeviceDetector $device)
    {
        $userAgentStr = DeviceDetectorFactory::getNormalizedUserAgent($userAgentStr);
        $outputArray = array(
            'bot' => $device->getBot(),
            'brand' => $device->getBrand(),
            'client' => $device->getClient(),
            'device' => $device->getDevice(),
            'model' => $device->getModel(),
            'os' => $device->getOs()
        );
        $outputPath = DeviceDetectorCacheEntry::getCachePath($userAgentStr, true);
        $content = "<?php return " . var_export($outputArray, true) . ";";
        file_put_contents($outputPath, $content, LOCK_EX);
    }
}
