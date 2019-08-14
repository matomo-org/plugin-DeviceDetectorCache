<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use DeviceDetector\DeviceDetector;
use Piwik\Filesystem;
use Piwik\Plugin\Manager;

class DeviceDetectorCacheEntry extends DeviceDetector
{
    const CACHE_DIR = "/cache/";

    public function __construct($userAgent)
    {
        parent::setUserAgent($userAgent);
        $values = include(self::getCachePath($userAgent));
        $this->bot = $values['bot'];
        $this->brand = $values['brand'];
        $this->client = $values['client'];
        $this->device = $values['device'];
        $this->model = $values['model'];
        $this->os = $values['os'];
    }

    public static function isCached($userAgent)
    {
        return file_exists(self::getCachePath($userAgent));
    }

    public static function getCachePath($userAgent, $createDirs = false)
    {
        $hashedUserAgent = md5($userAgent);

        // We use hash subdirs so we don't have 1000s of files in the one dir
        $cacheDir = self::getCacheDir();
        $hashDir = $cacheDir . substr($hashedUserAgent, 0, 2);

        if ($createDirs) {
            file_exists($cacheDir) || Filesystem::mkdir($cacheDir);
            file_exists($hashDir) || Filesystem::mkdir($hashDir);
        }

        return $hashDir . '/' . $hashedUserAgent . '.php';
    }

    public static function getCacheDir()
    {
        return Manager::getPluginDirectory('DeviceDetectorCache') . self::CACHE_DIR;
    }
}