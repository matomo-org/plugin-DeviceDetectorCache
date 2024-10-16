<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\Integration;

use Piwik\Config;
use Piwik\Plugins\DeviceDetectorCache\Configuration;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DeviceDetectorCache
 * @group ConfigurationTest
 * @group Configuration
 * @group Plugins
 */
class ConfigurationTest extends IntegrationTestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function setUp(): void
    {
        parent::setUp();

        $this->configuration = new Configuration();
        $this->configuration->install();
    }

    public function test_shouldInstallConfig()
    {
        $this->configuration->install();

        $configs = Config::getInstance()->DeviceDetectorCache;
        $this->assertEquals([
            'num_cache_entries' => '200000',
            'access_log_path'   => '/var/log/httpd/access_log',
            'access_log_regex'  => Configuration::DEFAULT_ACCESS_LOG_REGEX,
            'regex_match_entry' => 14,
        ], $configs);
    }

    public function test_getRegexMatchEntry()
    {
        $this->assertSame(Configuration::DEFAULT_ACCESS_LOG_REGEX_MATCH_ENTRY, $this->configuration->getRegexMatchEntry());
    }

    public function test_getRegexMatchEntry_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = [
            Configuration::KEY_ACCESS_LOG_REGEX_MATCH_ENTRY => '5',
        ];
        $this->assertEquals(5, $this->configuration->getRegexMatchEntry());
    }

    public function test_getAccessLogPath()
    {
        $this->assertSame(Configuration::DEFAULT_ACCESS_LOG_PATH, $this->configuration->getAccessLogPath());
    }

    public function test_getAccessLogPath_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = [
            Configuration::KEY_ACCESS_LOG_PATH => '/var/log/foo',
        ];
        $this->assertEquals('/var/log/foo', $this->configuration->getAccessLogPath());
    }

    public function test_getNumEntriesToCache()
    {
        $this->assertSame(Configuration::DEFAULT_NUM_ENTRIES_TO_CACHE, $this->configuration->getNumEntriesToCache());
    }

    public function test_getNumEntriesToCache_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = [
            Configuration::KEY_NUM_ENTRIES_TO_CACHE => '145',
        ];
        $this->assertEquals(145, $this->configuration->getNumEntriesToCache());
    }

    public function test_getAccessLogRegex()
    {
        $this->assertSame(Configuration::DEFAULT_ACCESS_LOG_REGEX, $this->configuration->getAccessLogRegex());
    }

    public function test_getAccessLogRegex_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = [
            Configuration::KEY_ACCESS_LOG_REGEX => '(.*)',
        ];
        $this->assertEquals('(.*)', $this->configuration->getAccessLogRegex());
    }
}
