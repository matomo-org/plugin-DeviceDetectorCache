<?php
/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
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
        $this->assertEquals(array(
            'num_cache_entries' => '200000',
            'access_log_path' => '/var/log/httpd/access_log',
            'access_log_regex' => Configuration::DEFAULT_AccessLogRegex,
            'regex_match_entry' => 14
        ), $configs);
    }

    public function test_getRegexMatchEntry()
    {
        $this->assertSame(Configuration::DEFAULT_AccessLogRegexMatchEntry, $this->configuration->getRegexMatchEntry());
    }

    public function test_getRegexMatchEntry_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = array(
            Configuration::KEY_AccessLogRegexMatchEntry => '5'
        );
        $this->assertEquals(5, $this->configuration->getRegexMatchEntry());
    }

    public function test_getAccessLogPath()
    {
        $this->assertSame(Configuration::DEFAULT_AccessLogPath, $this->configuration->getAccessLogPath());
    }

    public function test_getAccessLogPath_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = array(
            Configuration::KEY_AccessLogPath => '/var/log/foo'
        );
        $this->assertEquals('/var/log/foo', $this->configuration->getAccessLogPath());
    }

    public function test_getNumEntriesToCache()
    {
        $this->assertSame(Configuration::DEFAULT_NumEntriesToCache, $this->configuration->getNumEntriesToCache());
    }

    public function test_getNumEntriesToCache_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = array(
            Configuration::KEY_NumEntriesToCache => '145'
        );
        $this->assertEquals(145, $this->configuration->getNumEntriesToCache());
    }

    public function test_getAccessLogRegex()
    {
        $this->assertSame(Configuration::DEFAULT_AccessLogRegex, $this->configuration->getAccessLogRegex());
    }

    public function test_getAccessLogRegex_customValue()
    {
        Config::getInstance()->DeviceDetectorCache = array(
            Configuration::KEY_AccessLogRegex => '(.*)'
        );
        $this->assertEquals('(.*)', $this->configuration->getAccessLogRegex());
    }


}
