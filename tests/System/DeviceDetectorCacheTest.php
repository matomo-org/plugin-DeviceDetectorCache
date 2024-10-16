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

        $apiToTest   = [];
        $apiToTest[] = [
            $api,
            [
                'idSite'     => 1,
                'date'       => self::$fixture->dateTime,
                'periods'    => ['day'],
                'testSuffix' => '',
                // fields were added in Matomo 5.2-alpha, so ignore them as they are not relevant for this plugin anyways
                'xmlFieldsToRemove' => ['referrerType', 'referrerName', 'referrerKeyword']
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
