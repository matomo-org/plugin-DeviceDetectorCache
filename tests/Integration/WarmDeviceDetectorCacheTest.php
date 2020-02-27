<?php


namespace Piwik\Plugins\DeviceDetectorCache\tests\Integration;

use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCacheEntry;
use Piwik\Plugins\DeviceDetectorCache\Commands\WarmDeviceDetectorCache;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCacheFactory;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

class WarmDeviceDetectorCacheTest extends ConsoleCommandTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DeviceDetectorCacheEntry::setCacheDir('/testcache/');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        DeviceDetectorCacheEntry::clearCacheDir();
        if (file_exists(DeviceDetectorCacheEntry::getCacheDir())) {
            rmdir(DeviceDetectorCacheEntry::getCacheDir());
        }
    }

    public function testWritesUserAgentsToFile()
    {
        $testFile = __DIR__ . '/files/useragents1.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile
        ));

        $userAgents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko'
        );

        self::assertStringContainsString("Written 3 cache entries to file", $this->applicationTester->getDisplay());
        foreach ($userAgents as $userAgent) {
            $this->assertUserAgentWrittenToFile($userAgent);
        }
    }

    public function testInputFileDoesntExist()
    {
        $testFile = __DIR__ . '/files/notarealfile.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile
        ));

        self::assertStringContainsString("File $testFile not found", $this->applicationTester->getDisplay());
    }

    public function testNotSkippingHeaderRow()
    {
        $testFile = __DIR__ . '/files/useragentsnoheader.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile,
            '--skip-header-row' => false
        ));

        $userAgents = array(
            'Mozilla/5.0 (iPad; CPU OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Dalvik/2.1.0 (Linux; U; Android 6.0.1; SM-J510FN Build/MMB29M)'
        );

        self::assertStringContainsString("Written 3 cache entries to file", $this->applicationTester->getDisplay());
        foreach ($userAgents as $userAgent) {
            $this->assertUserAgentWrittenToFile($userAgent);
        }
    }

    public function testSkipsUserAgentsInIgnoreList()
    {
        $testFile = __DIR__ . '/files/useragentstoignore.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile
        ));

        self::assertStringContainsString("Written 0 cache entries to file", $this->applicationTester->getDisplay());

        $userAgent = 'Amazon-Route53-Health-Check-Service (ref d14cb74a-74d4-4400-940d-1579e3f0181b; report http://amzn.to/1vsZADi)';
        $this->assertUserAgentNotWrittenToFile($userAgent);
    }

    public function testClearsExistingFilesFromCacheWhenOptionPassed()
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 8.0.0; SAMSUNG SM-G930F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/9.4 Chrome/67.0.3396.87 Mobile Safari/537.36';
        $cacheFilePath = DeviceDetectorCacheEntry::getCachePath($userAgent, true);
        $cacheHashDir = dirname($cacheFilePath);

        file_put_contents($cacheFilePath, "<?php return array();", LOCK_EX);

        $this->assertFileExists($cacheFilePath);
        $this->assertFileExists($cacheHashDir);
        $testFile = __DIR__ . '/files/useragents1.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            '--clear' => true,
            'input-file' => $testFile
        ));

        // It wasn't in the list of user agents from the CSV file so it should have been removed
        // Folder should be removed too as there's no useragents that should have been written there
        $this->assertFileNotExists($cacheFilePath);
        $this->assertFileNotExists($cacheHashDir);
    }

    public function testDoesntClearExistingFilesFromCacheByDefault()
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 8.0.0; SAMSUNG SM-G930F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/9.4 Chrome/67.0.3396.87 Mobile Safari/537.36';
        $cacheFilePath = DeviceDetectorCacheEntry::getCachePath($userAgent, true);
        $cacheHashDir = dirname($cacheFilePath);

        file_put_contents($cacheFilePath, "<?php return array('testval' => 'testresult');", LOCK_EX);

        $testFile = __DIR__ . '/files/useragents1.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile
        ));

        $this->assertFileExists($cacheFilePath);
        $this->assertEquals(array('testval' => 'testresult'), include($cacheFilePath));
    }

    public function testDoesntProcessAllRowsWhenCounterSet()
    {
        $testFile = __DIR__ . '/files/useragents1.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile,
            '--count' => 2
        ));

        $userAgents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko'
        );

        self::assertStringContainsString("Written 2 cache entries to file", $this->applicationTester->getDisplay());
        $this->assertUserAgentWrittenToFile($userAgents[0]);
        $this->assertUserAgentWrittenToFile($userAgents[1]);
        $this->assertUserAgentNotWrittenToFile($userAgents[2]);
    }

    public function testVeryLongUserAgent()
    {
        $testFile = __DIR__ . '/files/useragentsverylong.csv';

        $this->applicationTester->run(array(
            'command' => WarmDeviceDetectorCache::COMMAND_NAME,
            'input-file' => $testFile,
            '--skip-header-row' => false
        ));

        $userAgentStr = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36 QBCore/3.53.1159.400 QQBrowser/9.0.2524.400 Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 MicroMessenger/6.5.2.501 NetType/WIFI WindowsWechat AndHereIsAVeryLongStringJustToMakeSureThatWeHitOver500CharactersWithThisRidiculouslyLongUserAgentThatIsLikelyToBreakOurCodeBecauseWeTruncateTheUserAgentAt500CharactersThatIsAnAwfulLotOfCharactersImStillGoing";

        self::assertStringContainsString("Written 1 cache entries to file", $this->applicationTester->getDisplay());
        $this->assertUserAgentWrittenToFile($userAgentStr);
    }

    private function assertUserAgentNotWrittenToFile($userAgent)
    {
        $expectedFilePath = DeviceDetectorCacheEntry::getCachePath($userAgent);
        $this->assertFileNotExists($expectedFilePath);
    }

    private function assertUserAgentWrittenToFile($userAgent)
    {
        $expectedFilePath = DeviceDetectorCacheEntry::getCachePath($userAgent);
        $this->assertFileExists($expectedFilePath);

        DeviceDetectorFactory::clearInstancesCache();
        $cacheFactory = new DeviceDetectorCacheFactory();
        $deviceDetectionFromFile = $cacheFactory->makeInstance($userAgent);

        DeviceDetectorFactory::clearInstancesCache();
        $parsingFactory = new DeviceDetectorFactory(); 
        $deviceDetectionParsed = $parsingFactory->makeInstance($userAgent);

        $this->assertInstanceOf("\Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCacheEntry", $deviceDetectionFromFile);
        $this->assertInstanceOf("\DeviceDetector\DeviceDetector", $deviceDetectionParsed);
        $this->assertEquals($deviceDetectionParsed->getBot(), $deviceDetectionFromFile->getBot());
        $this->assertEquals($deviceDetectionParsed->getBrand(), $deviceDetectionFromFile->getBrand());
        $this->assertEquals($deviceDetectionParsed->getClient(), $deviceDetectionFromFile->getClient());
        $this->assertEquals($deviceDetectionParsed->getDevice(), $deviceDetectionFromFile->getDevice());
        $this->assertEquals($deviceDetectionParsed->getModel(), $deviceDetectionFromFile->getModel());
        $this->assertEquals($deviceDetectionParsed->getOs(), $deviceDetectionFromFile->getOs());
    }
}