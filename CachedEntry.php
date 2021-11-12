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
use Piwik\Container\StaticContainer;
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;

class CachedEntry extends DeviceDetector
{
    private static $CACHE_DIR = '';
    private static $customCache = null;

    public function __construct($userAgent, $values)
    {
        parent::__construct($userAgent);
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
        if (self::getCached($userAgent)) {
            return; // already cached
        }

        $userAgent = DeviceDetectorFactory::getNormalizedUserAgent($userAgent);

        if (empty(self::$customCache)) {
            self::$customCache = StaticContainer::get('DeviceDetector\Cache\Cache');
        }

        // we don't use device detector factory because this way we can cache the cache instance and
        // lower memory since the factory would store an instance of every user agent in a static variable
        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->discardBotInformation();
        $deviceDetector->setCache(self::$customCache);
        $deviceDetector->parse();

        $outputArray = array(
            'bot' => $deviceDetector->getBot(),
            'brand' => $deviceDetector->getBrandName(),
            'client' => $deviceDetector->getClient(),
            'device' => $deviceDetector->getDevice(),
            'model' => $deviceDetector->getModel(),
            'os' => $deviceDetector->getOs()
        );
        $outputPath = self::getCachePath($userAgent, true);
        $content = "<?php return " . var_export($outputArray, true) . ";";
        file_put_contents($outputPath, $content, LOCK_EX);

        return $outputPath;
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
        if (empty(self::$CACHE_DIR)) {
            self::$CACHE_DIR = rtrim(PIWIK_DOCUMENT_ROOT, '/') . '/tmp/devicecache/';
        }
        return self::$CACHE_DIR;
    }

    /**
     * @internal
     * tests only
     */
    public static function clearCacheDir()
    {
        $path = self::getCacheDir();
        if (!empty($path)
            && is_dir($path)
            && strpos($path, PIWIK_DOCUMENT_ROOT) === 0) {
            // fastest way to delete that many files (we'll delete potentially 200K files and more)

            Filesystem::unlinkRecursive(self::getCacheDir(), false);
        }
    }

    public static function getNumEntriesInCacheDir()
    {
        $files = self::getCacheFilesInCacheDir();
        return count($files);
    }

    private static function getCacheFilesInCacheDir()
    {
        $path = rtrim(self::getCacheDir(), '/');
        return array_filter(Filesystem::globr($path, '*.php'), function($file) {
            return strpos($file, 'index.php') === false;
        });
    }

    public static function deleteLeastAccessedFiles($numFilesToDelete)
    {
        if ($numFilesToDelete < 1) {
            return; // nothing to delete
        }
        $files = self::getCacheFilesInCacheDir();
        $accessed = array();
        foreach ($files as $file) {
            $accessed[$file] = fileatime($file);
        }

        // have most recently accessed files at the end of the array and delete entries from the beginning of the array
        asort($accessed, SORT_NATURAL);

        $numFilesDeleted = 1;
        foreach ($accessed as $file => $time) {
            if ($numFilesDeleted > $numFilesToDelete) {
                break;
            } else {
                Filesystem::deleteFileIfExists($file);
                $numFilesDeleted++;
            }
        }

    }
}