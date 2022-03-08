<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;
use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Piwik\Plugins\DeviceDetectorCache\Factory;

class DeviceDetectorCacheFactoryTest extends TestCase
{
    public function setUp(): void
    {
        DeviceDetectorFactory::clearInstancesCache();
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT . '/tmp/devicecachetests/');
    }

    public function tearDown(): void
    {
        CachedEntry::clearCacheDir();
        Filesystem::unlinkRecursive(CachedEntry::getCacheDir(), true);
    }

    public function testGetInstanceFromCache()
    {
        $userAgent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0";
        $expected  = [
            'bot'    => null,
            'brand'  => 'Cooper',
            'client' => [
                'type' => 'browser',
                'name' => 'Microsoft Edge',
            ],
            'device' => 1,
            'model'  => 'iPhone',
            'os'     => [
                'name' => 'Linux',
            ],
        ];

        self::writeFakeFile($expected, $userAgent);

        $factory         = new Factory();
        $deviceDetection = $factory->makeInstance($userAgent, []);
        $this->assertInstanceOf(CachedEntry::class, $deviceDetection);
        $this->assertEquals(null, $deviceDetection->getBot());
        $this->assertEquals('Cooper', $deviceDetection->getBrandName());
        $this->assertEquals($expected['client'], $deviceDetection->getClient());
        $this->assertEquals(1, $deviceDetection->getDevice());
        $this->assertEquals('iPhone', $deviceDetection->getModel());
        $this->assertEquals($expected['os'], $deviceDetection->getOs());
    }

    public function testGetInstanceFromCacheWithClientHints()
    {
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36 Edg/97.0.1072.66";
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
        $expected  = [
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

        self::writeFakeFile($expected, $userAgent);

        $factory         = new Factory();

        // not using client hints will return the cached entry
        $deviceDetection = $factory->makeInstance($userAgent, []);
        $this->assertInstanceOf(CachedEntry::class, $deviceDetection);
        $this->assertEquals(null, $deviceDetection->getBot());
        $this->assertEquals($expected['brand'], $deviceDetection->getBrandName());
        $this->assertEquals($expected['client'], $deviceDetection->getClient());
        $this->assertEquals(1, $deviceDetection->getDevice());
        $this->assertEquals($expected['model'], $deviceDetection->getModel());
        $this->assertEquals($expected['os'], $deviceDetection->getOs());

        // using client hints will overwrite some value if needed
        $deviceDetection = $factory->makeInstance($userAgent, $clientHints);
        $expected['os'] = [
            'name' => 'Windows',
            'version' => '11',
            'short_name' => 'WIN',
            'platform' => 'x64',
            'family' => 'Windows',
        ];
        $expected['client'] = [
            'type' => 'browser',
            'name' => 'Microsoft Edge',
            'version' => '98.0',
            'short_name' => 'PS',
            'engine' => 'Blink',
            'engine_version' => '97.0.4692.71',
            'family' => 'Internet Explorer',
        ];
        $this->assertInstanceOf(CachedEntry::class, $deviceDetection);
        $this->assertEquals(null, $deviceDetection->getBot());
        $this->assertEquals($expected['brand'], $deviceDetection->getBrandName());
        $this->assertEquals($expected['client'], $deviceDetection->getClient());
        $this->assertEquals(1, $deviceDetection->getDevice());
        $this->assertEquals($expected['model'], $deviceDetection->getModel());
        $this->assertEquals($expected['os'], $deviceDetection->getOs());
    }

    public function testGetInstanceFromDeviceDetector()
    {
        $userAgent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0";
        $expected  = [
            'client' => [
                'type'           => 'browser',
                'name'           => 'Firefox',
                'short_name'     => 'FF',
                'version'        => '33.0',
                'engine'         => 'Gecko',
                'engine_version' => '33.0',
            ],
            'os'     => [
                'name'       => 'Mac',
                'short_name' => 'MAC',
                'version'    => '10.10',
                'platform'   => '',
            ],
        ];

        $factory         = new Factory();
        $deviceDetection = $factory->makeInstance($userAgent, []);
        $this->assertInstanceOf("\DeviceDetector\DeviceDetector", $deviceDetection);
        $this->assertEquals(null, $deviceDetection->getBot());
        $this->assertEquals('AP', $deviceDetection->getBrand());
        $client = $deviceDetection->getClient();
        unset($client['family']); // newer version of DD might return the family. We ignore it here, to allow tests with older Matomo versions
        $this->assertEquals($expected['client'], $client);
        $this->assertEquals(0, $deviceDetection->getDevice());
        $this->assertEquals('', $deviceDetection->getModel());
        $os = $deviceDetection->getOs();
        unset($os['family']); // newer version of DD might return the family. We ignore it here, to allow tests with older Matomo versions
        $this->assertEquals($expected['os'], $os);
    }

    public static function writeFakeFile($expected, $userAgent)
    {
        $filePath = CachedEntry::getCachePath($userAgent, true);
        $content  = "<?php return " . var_export($expected, true) . ";";
        file_put_contents($filePath, $content, LOCK_EX);
    }
}
