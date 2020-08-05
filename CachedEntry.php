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
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;
use Piwik\Plugin\Manager;

class CachedEntry extends DeviceDetector
{
    private static $CACHE_DIR = '';

    public function __construct($userAgent, $values)
    {
        parent::setUserAgent($userAgent);
        $this->bot = $values['bot'];
        $this->brand = $values['brand'];
        $this->client = $values['client'];
        $this->device = $values['device'];
        $this->model = $values['model'];
        $this->os = $values['os'];
    }

    public static function getCached($userAgent)
    {
        // we check if file exists and include the file here directly as it needs to be kind of atomic...
        // if we only checked if file exists, and then choose to use cached entry which would then include the file,
        // then there's a risk that between the file_exists and the include the cache file was removed
        $path = self::getCachePath($userAgent);
        $exists = file_exists($path);
        if ($exists) {
            $values = @include($path);
            if (!empty($values) && is_array($values) && isset($values['os'])) {
                return $values;
            }
        }
    }

    public static function writeToCache($userAgent)
    {
        $userAgent = DeviceDetectorFactory::getNormalizedUserAgent($userAgent);
        $factory = new DeviceDetectorFactory();
        $device = $factory->makeInstance($userAgent);
        $outputArray = array(
            'bot' => $device->getBot(),
            'brand' => $device->getBrand(),
            'client' => $device->getClient(),
            'device' => $device->getDevice(),
            'model' => $device->getModel(),
            'os' => $device->getOs()
        );
        $outputPath = self::getCachePath($userAgent, true);
        $content = "<?php return " . var_export($outputArray, true) . ";";
        file_put_contents($outputPath, $content, LOCK_EX);
    }

    public static function getCachePath($userAgent, $createDirs = false)
    {
        $userAgent = DeviceDetectorFactory::getNormalizedUserAgent($userAgent);
        $hashedUserAgent = md5($userAgent);

        // We use hash subdirs so we don't have 1000s of files in the one dir
        $cacheDir = self::getCacheDir();
        $hashDir = $cacheDir . substr($hashedUserAgent, 0, 3);

        if ($createDirs) {
            if (!is_dir($cacheDir)) {
                Filesystem::mkdir($cacheDir);
            }
            if (!is_dir($hashDir)) {
                Filesystem::mkdir($hashDir);
            }
        }

        return $hashDir . '/' . $hashedUserAgent . '.php';
    }

    public static function setCacheDir($cacheDir)
    {
        self::$CACHE_DIR = $cacheDir;
    }

    public static function getCacheDir()
    {
        if (self::$CACHE_DIR) {
            return self::$CACHE_DIR;
        }
        return rtrim(PIWIK_DOCUMENT_ROOT, '/') . '/tmp/devicecache/';
    }

    public static function clearCacheDir()
    {
        $path = self::getCacheDir();
        if (!empty($path)
            && is_dir($path)
            && strpos($path, PIWIK_DOCUMENT_ROOT) === 0) {
            // fastest way to delete that many files (we'll delete potentially 200K files and more)
            exec('find ' . escapeshellarg(rtrim(self::getCacheDir(), '/')) . ' -name "*.php" -exec rm {} \;');
        }
    }
}