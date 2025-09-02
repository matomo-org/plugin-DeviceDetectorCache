<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\System;

use Piwik\Plugins\DeviceDetectorCache\tests\Fixtures\SimpleFixtureTrackFewVisits;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Version;

/**
 * @group DeviceDetectorCache
 * @group DeviceDetectorCacheTest
 * @group Plugins
 */
class DeviceDetectorCacheTest extends SystemTestCase
{
    /**
     * @var SimpleFixtureTrackFewVisits
     */
    public static $fixture = null; // initialized below class definition

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $api = [
            'Live.getLastVisitsDetails',
        ];

        $columnsToHide = [];

        if (version_compare(Version::VERSION, '5.2.0-alpha', '<')) {
            // In Matomo 5.2 referrer columns had been added to ecommerce actions. For tests with older Matomo releases we therefor ignore those columns
            $columnsToHide = array_merge($columnsToHide, ['referrerType', 'referrerName', 'referrerKeyword']);
        }

        if (version_compare(Version::VERSION, '5.5.0-b1', '<')) {
            // In Matomo 5.5 ai referrer had been added
            $columnsToHide = array_merge($columnsToHide, ['referrerAIAssistantUrl', 'referrerAIAssistantIcon']);
        }

        $apiToTest   = [];
        $apiToTest[] = [
            $api,
            [
                'idSite'     => 1,
                'date'       => self::$fixture->dateTime,
                'periods'    => ['day'],
                'testSuffix' => '',
                'xmlFieldsToRemove' => $columnsToHide,
            ],
        ];

        return $apiToTest;
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

DeviceDetectorCacheTest::$fixture = new SimpleFixtureTrackFewVisits();
