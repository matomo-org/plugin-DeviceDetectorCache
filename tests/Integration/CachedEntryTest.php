<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\Integration;

use Piwik\Filesystem;
use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group DeviceDetectorCache
 * @group CachedEntryTest
 * @group Plugins
 */
class CachedEntryTest extends ConsoleCommandTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT . '/tmp/devicecachetests/');
        CachedEntry::clearCacheDir();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        CachedEntry::clearCacheDir();
        Filesystem::unlinkRecursive(CachedEntry::getCacheDir(), true);
    }

    public function testConstructEmptyParams()
    {
        $instance = new CachedEntry('', [], []);
        $this->assertIsObject($instance);
        $this->assertInstanceOf(CachedEntry::class, $instance);
    }

    public function testConstructSomeValues()
    {
        $values = [
            'bot' => 'testBot',
            'brand' => 'testBrand',
            'client'=> 'testClient',
            'device'=> 2,
            'model'=> 'testModel',
            'os' => 'testOs',
        ];

        $instance = new CachedEntry('', [], $values);
        $this->assertIsObject($instance);
        $this->assertInstanceOf(CachedEntry::class, $instance);
        $this->assertSame($values['bot'], $instance->getBot());
        $this->assertSame($values['brand'], $instance->getBrandName());
        $this->assertSame($values['client'], $instance->getClient());
        $this->assertSame($values['device'], $instance->getDevice());
        $this->assertSame($values['model'], $instance->getModel());
        $this->assertSame($values['os'], $instance->getOs());
    }

    public function testConstruct()
    {
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36 Edg/97.0.1072.66";

        $values  = [
            'bot'    => null,
            'brand'  => '',
            'client' => [
                'type' => 'browser',
                'name' => 'Microsoft Edge',
                'version' => '97.0.1072.66'
            ],
            'device' => 1,
            'model'  => '',
            'os'     => [
                'name' => 'Windows',
                'version' => '10',
            ],
        ];

        $clientHints = [
            'HTTP_SEC_CH_UA_PLATFORM' => '"Windows"',
            'HTTP_SEC_CH_UA' => '" Not A;Brand";v="99", "Chromium";v="95", "Microsoft Edge";v="95"',
            'HTTP_SEC_CH_UA_MOBILE' => "?0",
            'HTTP_SEC_CH_UA_FULL_VERSION' => '"98.0.0.1"',
            'HTTP_SEC_CH_UA_PLATFORM_VERSION' => '"14.0.0"',
            'HTTP_SEC_CH_UA_ARCH' => "x86",
            'HTTP_SEC_CH_UA_BITNESS' => "64",
            'HTTP_SEC_CH_UA_MODEL' => ""
        ];

        $expectedClient = [
            'type' => 'browser',
            'name' => 'Microsoft Edge',
            'short_name' => 'PS',
            'version' => '98.0',
            'engine' => '',
            'engine_version' => '',
            'family' => 'Internet Explorer',
        ];

        $expectedOs = [
            'name' => 'Windows',
            'short_name' => 'WIN',
            'version' => '11',
            'platform' => 'x64',
            'family' => 'Windows',
        ];

        $instance = new CachedEntry($userAgent, $clientHints, $values);
        $this->assertIsObject($instance);
        $this->assertInstanceOf(CachedEntry::class, $instance);
        $this->assertSame(null, $instance->getBot());
        $this->assertSame($values['brand'], $instance->getBrandName());
        $this->assertSame($expectedClient, $instance->getClient());
        $this->assertSame($values['device'], $instance->getDevice());
        $this->assertSame($values['model'], $instance->getModel());
        $this->assertSame($expectedOs, $instance->getOs());
    }

    public function testConstructDefault()
    {
        $userAgent = "unknown";

        $clientHints = [
            'HTTP_SEC_CH_UA_PLATFORM' => '"Windows"',
            'HTTP_SEC_CH_UA' => '" Not A;Brand";v="99", "Chromium";v="95", "Microsoft Edge";v="95"',
            'HTTP_SEC_CH_UA_MOBILE' => "?0",
            'HTTP_SEC_CH_UA_FULL_VERSION' => '"98.0.0.1"',
            'HTTP_SEC_CH_UA_PLATFORM_VERSION' => '"14.0.0"',
            'HTTP_SEC_CH_UA_ARCH' => "x86",
            'HTTP_SEC_CH_UA_BITNESS' => "64",
            'HTTP_SEC_CH_UA_MODEL' => ""
        ];

        $expectedOs = [
            'name' => 'Windows',
            'short_name' => 'WIN',
            'version' => '11',
            'platform' => 'x64',
            'family' => 'Windows',
        ];


        $instance = new CachedEntry($userAgent, $clientHints, []);
        $this->assertIsObject($instance);
        $this->assertInstanceOf(CachedEntry::class, $instance);
        $this->assertSame(null, $instance->getBot());
        $this->assertSame('', $instance->getBrandName());
        $this->assertSame(null, $instance->getClient());
        $this->assertSame(null, $instance->getDevice());
        $this->assertSame('', $instance->getModel());
        $this->assertSame($expectedOs, $instance->getOs());
    }

    public function testGetNumCacheFiles_noneCached()
    {
        $this->assertEquals(0, CachedEntry::getNumEntriesInCacheDir());
    }

    public function testGetNumCacheFiles()
    {
        CachedEntry::writeToCache('foo', []);
        $this->assertEquals(1, CachedEntry::getNumEntriesInCacheDir());
        CachedEntry::writeToCache('bar', []);
        $this->assertEquals(2, CachedEntry::getNumEntriesInCacheDir());
        CachedEntry::writeToCache('baz', []);
        $this->assertEquals(3, CachedEntry::getNumEntriesInCacheDir());
    }

    public function testGetCached_noEntry()
    {
        $this->assertNull(CachedEntry::getCached('foo', []));
    }

    public function test_writeToCache_GetCached()
    {
        CachedEntry::writeToCache('foo', []);
        $cacheEntry = CachedEntry::getCached('foo', []);
        $this->assertEquals(
            [
            'bot', 'brand', 'client', 'device', 'model', 'os'
            ],
            array_keys($cacheEntry)
        );
    }

    public function test_getCachePath()
    {
        $path1 = CachedEntry::getCachePath('foo');
        $path2 = CachedEntry::getCachePath('bar');

        $path1 = str_replace(PIWIK_DOCUMENT_ROOT, '', $path1);
        $path2 = str_replace(PIWIK_DOCUMENT_ROOT, '', $path2);

        $this->assertEquals('/tmp/devicecachetests/acb/acbd18db4cc2f85cedef654fccc4a4d8.php', $path1);
        $this->assertEquals('/tmp/devicecachetests/37b/37b51d194a7513e45b56f6524f2d51f2.php', $path2);
    }

    public function test_getCacheDir()
    {
        $path1 = CachedEntry::getCacheDir();

        // because we may delete files we want to make sure it starts with the matomo tmp path
        $this->assertSame(0, strpos($path1, PIWIK_DOCUMENT_ROOT . '/tmp/'));
    }

    public function test_deleteLeastAccessedFiles_nothingToDelete()
    {
        $filePath = CachedEntry::writeToCache('file', []);
        $this->assertFileExists($filePath);

        CachedEntry::deleteLeastAccessedFiles(-1);
        CachedEntry::deleteLeastAccessedFiles(0);

        $this->assertFileExists($filePath);
    }

    public function test_deleteLeastAccessedFiles_deletesOnlyOldest()
    {
        $filePath1 = CachedEntry::writeToCache('file', []);
        sleep(1); // otherwise without sleep the sorting won't work properly
        $filePath2 = CachedEntry::writeToCache('bar', []);
        sleep(1);
        $filePath3 = CachedEntry::writeToCache('baz', []);
        sleep(1);
        $filePath4 = CachedEntry::writeToCache('foo', []);
        sleep(1);

        CachedEntry::deleteLeastAccessedFiles(2);

        $this->assertFileNotExists($filePath1);
        $this->assertFileNotExists($filePath2);
        $this->assertFileExists($filePath3);
        $this->assertFileExists($filePath4);
    }

    public function test_deleteLeastAccessedFiles_deletesOnlyOldest2()
    {
        $filePath1 = CachedEntry::writeToCache('file', []);
        sleep(1);
        $filePath2 = CachedEntry::writeToCache('bar', []);
        sleep(1);
        $filePath3 = CachedEntry::writeToCache('baz', []);
        sleep(1);
        $filePath4 = CachedEntry::writeToCache('foo', []);
        sleep(1);

        touch($filePath1);
        sleep(1);
        touch($filePath3);

        CachedEntry::deleteLeastAccessedFiles(2);

        $this->assertFileNotExists($filePath2);
        $this->assertFileNotExists($filePath4);
        $this->assertFileExists($filePath1);
        $this->assertFileExists($filePath3);
    }
}
