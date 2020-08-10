<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\Integration;

use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;
use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Piwik\Plugins\DeviceDetectorCache\Commands\WarmDeviceDetectorCache;
use Piwik\Plugins\DeviceDetectorCache\Configuration;
use Piwik\Plugins\DeviceDetectorCache\Factory;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

class WarmDeviceDetectorCacheTest extends ConsoleCommandTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT. '/tmp/devicecachetests/');
        CachedEntry::clearCacheDir();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        CachedEntry::clearCacheDir();
        Filesystem::unlinkRecursive(CachedEntry::getCacheDir(), true);
    }

    private function setAccessLogFile($file)
    {
        $config                              = \Piwik\Config::getInstance();
        $d                                   = $config->DeviceDetectorCache;
        $d[Configuration::KEY_AccessLogPath] = $file;
        $config->DeviceDetectorCache         = $d;
    }

    private function setCountProcessNumEntries($numEntries)
    {
        $config                                  = \Piwik\Config::getInstance();
        $d                                       = $config->DeviceDetectorCache;
        $d[Configuration::KEY_NumEntriesToCache] = $numEntries;
        $config->DeviceDetectorCache             = $d;
    }

    public function testWritesUserAgentsToFile()
    {
        $testFile = __DIR__ . '/files/useragents1.csv';
        $this->setAccessLogFile($testFile);

        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        ];

        $this->assertStringContainsString("Written 3 cache entries to file", $this->applicationTester->getDisplay());
        foreach ($userAgents as $userAgent) {
            $this->assertUserAgentWrittenToFile($userAgent);
        }
    }

    public function testInputFileDoesntExist()
    {
        $testFile = __DIR__ . '/files/notarealfile.csv';
        $this->setAccessLogFile($testFile);

        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);

        $this->assertStringContainsString("Configured access log path does not exist", $this->applicationTester->getDisplay());
    }

    public function testDoesNotClearExistingFilesFromCacheByDefault()
    {
        $userAgent     = 'Mozilla/5.0 (Linux; Android 8.0.0; SAMSUNG SM-G930F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/9.4 Chrome/67.0.3396.87 Mobile Safari/537.36';
        $cacheFilePath = CachedEntry::getCachePath($userAgent, true);

        file_put_contents($cacheFilePath, "<?php return array('testval' => 'testresult');", LOCK_EX);

        $testFile = __DIR__ . '/files/useragents1.csv';
        $this->setAccessLogFile($testFile);

        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);

        $this->assertFileExists($cacheFilePath);
    }

    public function testDoesClearExistingFilesFromCacheByDefaultWhenTooManyEntriesExist()
    {
        // was last accessed
        $userAgentKept     = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';
        $userAgentDeleted  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36';
        $cacheFilePathKept = CachedEntry::getCachePath($userAgentKept, true);
        $cacheFilePathDeleted = CachedEntry::getCachePath($userAgentDeleted, true);

        $this->setCountProcessNumEntries(3);

        $testFile = __DIR__ . '/files/useragents1.csv';
        $this->setAccessLogFile($testFile);

        //cache 3 entries
        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);
        $this->assertFileExists($cacheFilePathKept);
        $this->assertFileExists($cacheFilePathDeleted);

        $this->assertEquals(3, CachedEntry::getNumEntriesInCacheDir());

        $this->setCountProcessNumEntries(1);

        // now we run again and it should delete 2 of the entries
        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);
        // should now have deleted 2 files
        $this->assertEquals(1, CachedEntry::getNumEntriesInCacheDir());

        $this->assertFileExists($cacheFilePathKept);
        $this->assertFileNotExists($cacheFilePathDeleted);
    }

    public function testDoesntProcessAllRowsWhenCounterSet()
    {
        $testFile = __DIR__ . '/files/useragents1.csv';
        $this->setAccessLogFile($testFile);
        $this->setCountProcessNumEntries(2);

        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        ];

        $this->assertStringContainsString("Written 2 cache entries to file", $this->applicationTester->getDisplay());
        $this->assertUserAgentWrittenToFile($userAgents[0]);
        $this->assertUserAgentWrittenToFile($userAgents[1]);
        $this->assertUserAgentNotWrittenToFile($userAgents[2]);
    }

    public function testVeryLongUserAgent()
    {
        $testFile = __DIR__ . '/files/useragentsverylong.csv';
        $this->setAccessLogFile($testFile);

        $this->applicationTester->run([
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
        ]);

        $userAgentStr = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36 QBCore/3.53.1159.400 QQBrowser/9.0.2524.400 Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 MicroMessenger/6.5.2.501 NetType/WIFI WindowsWechat AndHereIsAVeryLongStringJustToMakeSureThatWeHitOver500CharactersWithThisRidiculouslyLongUserAgentThatIsLikelyToBreakOurCodeBecauseWeTruncateTheUserAgentAt500CharactersThatIsAnAwfulLotOfCharactersImStillGoing";

        $this->assertStringContainsString("Written 1 cache entries to file", $this->applicationTester->getDisplay());
        $this->assertUserAgentWrittenToFile($userAgentStr);
    }

    private function assertUserAgentNotWrittenToFile($userAgent)
    {
        $expectedFilePath = CachedEntry::getCachePath($userAgent);
        $this->assertFileNotExists($expectedFilePath);
    }

    private function assertUserAgentWrittenToFile($userAgent)
    {
        $expectedFilePath = CachedEntry::getCachePath($userAgent);
        $this->assertFileExists($expectedFilePath);

        DeviceDetectorFactory::clearInstancesCache();
        $cacheFactory            = new Factory();
        $deviceDetectionFromFile = $cacheFactory->makeInstance($userAgent);

        DeviceDetectorFactory::clearInstancesCache();
        $parsingFactory        = new DeviceDetectorFactory();
        $deviceDetectionParsed = $parsingFactory->makeInstance($userAgent);

        $this->assertInstanceOf(CachedEntry::class, $deviceDetectionFromFile);
        $this->assertInstanceOf("\DeviceDetector\DeviceDetector", $deviceDetectionParsed);
        $this->assertEquals($deviceDetectionParsed->getBot(), $deviceDetectionFromFile->getBot());
        $this->assertEquals($deviceDetectionParsed->getBrand(), $deviceDetectionFromFile->getBrand());
        $this->assertEquals($deviceDetectionParsed->getClient(), $deviceDetectionFromFile->getClient());
        $this->assertEquals($deviceDetectionParsed->getDevice(), $deviceDetectionFromFile->getDevice());
        $this->assertEquals($deviceDetectionParsed->getModel(), $deviceDetectionFromFile->getModel());
        $this->assertEquals($deviceDetectionParsed->getOs(), $deviceDetectionFromFile->getOs());
    }
}