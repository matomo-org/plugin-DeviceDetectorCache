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

class CachedEntryTest extends ConsoleCommandTestCase
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

    public function testGetNumCacheFiles_noneCached()
    {
        $this->assertEquals(0, CachedEntry::getNumEntriesInCacheDir());
    }

    public function testGetNumCacheFiles()
    {
        CachedEntry::writeToCache('foo');
        $this->assertEquals(1, CachedEntry::getNumEntriesInCacheDir());
        CachedEntry::writeToCache('bar');
        $this->assertEquals(2, CachedEntry::getNumEntriesInCacheDir());
        CachedEntry::writeToCache('baz');
        $this->assertEquals(3, CachedEntry::getNumEntriesInCacheDir());
    }

    public function testGetCached_noEntry()
    {
        $this->assertNull(CachedEntry::getCached('foo'));
    }

    public function test_writeToCache_GetCached()
    {
        CachedEntry::writeToCache('foo');
        $cacheEntry = CachedEntry::getCached('foo');
        $this->assertEquals(array(
            'bot', 'brand', 'client', 'device', 'model', 'os'
        )
        , array_keys($cacheEntry));
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
        $filePath = CachedEntry::writeToCache('file');
        $this->assertFileExists($filePath);

        CachedEntry::deleteLeastAccessedFiles(-1);
        CachedEntry::deleteLeastAccessedFiles(0);

        $this->assertFileExists($filePath);
    }

    public function test_deleteLeastAccessedFiles_deletesOnlyOldest()
    {
        $filePath1 = CachedEntry::writeToCache('file');
        sleep(1); // otherwise without sleep the sorting won't work properly
        $filePath2 = CachedEntry::writeToCache('bar');
        sleep(1);
        $filePath3 = CachedEntry::writeToCache('baz');
        sleep(1);
        $filePath4 = CachedEntry::writeToCache('foo');
        sleep(1);

        CachedEntry::deleteLeastAccessedFiles(2);

        $this->assertFileNotExists($filePath1);
        $this->assertFileNotExists($filePath2);
        $this->assertFileExists($filePath3);
        $this->assertFileExists($filePath4);
    }

    public function test_deleteLeastAccessedFiles_deletesOnlyOldest2()
    {
        $filePath1 = CachedEntry::writeToCache('file');
        sleep(1);
        $filePath2 = CachedEntry::writeToCache('bar');
        sleep(1);
        $filePath3 = CachedEntry::writeToCache('baz');
        sleep(1);
        $filePath4 = CachedEntry::writeToCache('foo');
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