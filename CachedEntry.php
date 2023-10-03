<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use Piwik\Container\StaticContainer;
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetector\CachedBrowserParser;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetector\CachedOperatingSystemParser;

class CachedEntry extends DeviceDetector
{
    private static $CACHE_DIR = '';
    private static $customCache = null;

    public function __construct(string $userAgent, $clientHints, array $values)
    {
        $clientHints = $clientHints ? ClientHints::factory($clientHints) : null;
        parent::__construct($userAgent, $clientHints);

        $this->bot = $values['bot'] ?? '';
        $this->brand = $values['brand'] ?? '';
        $this->client = $values['client'] ?? '';
        $this->device = $values['device'] ?? '';
        $this->model = $values['model'] ?? '';
        $this->os = $values['os'] ?? '';

        // Or cached entries only use the useragents, so if we have some client hints provided,
        // We use some special parsers, which use the cached user agent result and parses it again using client hints
        if (!empty($clientHints) && !empty($values['client']['type']) && $values['client']['type'] === 'browser') {
            $browserParser = new CachedBrowserParser($userAgent, $clientHints);
            $browserParser->setCachedResult($this->client);
            $this->client = $browserParser->parse();
        }

        if (!empty($clientHints)) {
            $osParser = new CachedOperatingSystemParser($userAgent, $clientHints);
            $osParser->setCachedResult($this->os);
            $this->os = $osParser->parse();
        }
    }

    public static function getCached(string $userAgent): ?array
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

        return null;
    }

    public static function writeToCache(string $userAgent): ?string
    {
        if (self::getCached($userAgent)) {
            return null; // already cached
        }

        if (empty(self::$customCache)) {
            self::$customCache = StaticContainer::get('DeviceDetector\Cache\Cache');
        }

        // we don't use device detector factory because this way we can cache the cache instance and
        // lower memory since the factory would store an instance of every user agent in a static variable
        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->discardBotInformation();
        $deviceDetector->setCache(self::$customCache);
        $deviceDetector->parse();

        $outputArray = [
            'bot' => $deviceDetector->getBot(),
            'brand' => $deviceDetector->getBrandName(),
            'client' => $deviceDetector->getClient(),
            'device' => $deviceDetector->getDevice(),
            'model' => $deviceDetector->getModel(),
            'os' => $deviceDetector->getOs()
        ];
        $outputPath = self::getCachePath($userAgent, true);
        $content = "<?php return " . var_export($outputArray, true) . ";";
        file_put_contents($outputPath, $content, LOCK_EX);

        return $outputPath;
    }

    public static function getCachePath(string $userAgent, bool $createDirs = false): string
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

    public static function setCacheDir(string $cacheDir): void
    {
        self::$CACHE_DIR = $cacheDir;
    }

    public static function getCacheDir(): string
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
    public static function clearCacheDir(): void
    {
        $path = self::getCacheDir();
        if (
            !empty($path)
            && is_dir($path)
            && strpos($path, PIWIK_DOCUMENT_ROOT) === 0
        ) {
            // fastest way to delete that many files (we'll delete potentially 200K files and more)

            Filesystem::unlinkRecursive(self::getCacheDir(), false);
        }
    }

    public static function getNumEntriesInCacheDir(): int
    {
        $files = self::getCacheFilesInCacheDir();
        return count($files);
    }

    private static function getCacheFilesInCacheDir(): array
    {
        $path = rtrim(self::getCacheDir(), '/');
        return array_filter(Filesystem::globr($path, '*.php'), function ($file) {
            return strpos($file, 'index.php') === false;
        });
    }

    public static function deleteLeastAccessedFiles(int $numFilesToDelete): void
    {
        if ($numFilesToDelete < 1) {
            return; // nothing to delete
        }
        $files = self::getCacheFilesInCacheDir();
        $accessed = [];
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
