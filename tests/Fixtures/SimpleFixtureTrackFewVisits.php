<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\tests\Fixtures;

use Piwik\Date;
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Filesystem;
use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Piwik\Plugins\DeviceDetectorCache\tests\Unit\DeviceDetectorCacheFactoryTest;
use Piwik\Tests\Framework\Fixture;

/**
 * Generates tracker testing data for our DeviceDetectorCacheTest
 *
 * This Simple fixture adds one website and tracks one visit with couple pageviews and an ecommerce conversion
 */
class SimpleFixtureTrackFewVisits extends Fixture
{
    public $dateTime = '2013-01-23 01:23:45';
    public $idSite = 1;

    private $userAgent1 = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';
    private $userAgent2 = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';


    public function setUp(): void
    {
        $this->setupCache();
        $this->setUpWebsite();
        $this->trackFirstVisit();
        DeviceDetectorFactory::clearInstancesCache();
    }

    /**
     * Write device data for the wrong useragent - means we have valid device data (so the OS etc fields
     * in the API output won't just be "Unknown") but we can distinguish between data read from the cache
     * and data that was parsed directly from the useragent string.
     */
    private function setupCache()
    {
        $expected = [
            'bot'    => null,
            'brand'  => 'Fly',
            'client' => [
                'type'           => 'browser',
                'name'           => 'Microsoft Edge',
                'short_name'     => 'PS',
                'version'        => '33.0',
                'engine'         => 'Gecko',
                'engine_version' => '',
            ],
            'device' => 1,
            'model'  => 'iPhone',
            'os'     => [
                'name'       => 'GNU/Linux',
                'short_name' => 'LIN',
                'version'    => '10.10',
                'platform'   => '',
            ],
        ];
        //user agent 2 should detect an iphone even though it is not as it's read from cache
        CachedEntry::setCacheDir(PIWIK_DOCUMENT_ROOT. '/tmp/devicecachetests/');
        DeviceDetectorCacheFactoryTest::writeFakeFile($expected, $this->userAgent2);
    }

    public function tearDown(): void
    {
        CachedEntry::clearCacheDir();
        Filesystem::unlinkRecursive(CachedEntry::getCacheDir(), true);
    }

    private function setUpWebsite()
    {
        if (!self::siteCreated($this->idSite)) {
            $idSite = self::createWebsite($this->dateTime, $ecommerce = 1);
            $this->assertSame($this->idSite, $idSite);
        }
    }

    protected function trackFirstVisit()
    {
        $t = self::getTracker($this->idSite, $this->dateTime, $defaultInit = true);
        $t->setIp('56.11.55.73');
        $t->setUserAgent($this->userAgent2);

        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.1)->getDatetime());
        $t->setUrl('http://example.com/sub/page');
        self::checkResponse($t->doTrackPageView('Viewing homepage'));

        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.2)->getDatetime());
        $t->setUrl('http://example.com/?search=this is a site search query');
        self::checkResponse($t->doTrackPageView('Site search query'));

        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.3)->getDatetime());
        $t->addEcommerceItem($sku = 'SKU_ID2', $name = 'A durable item', $category = 'Best seller', $price = 321);
        self::checkResponse($t->doTrackEcommerceCartUpdate($grandTotal = 33 * 77));

        $t = self::getTracker($this->idSite, $this->dateTime, $defaultInit = true);
        $t->setIp('56.11.55.74');
        $t->setUserAgent($this->userAgent1);

        $t->setForceVisitDateTime(Date::factory($this->dateTime)->addHour(0.1)->getDatetime());
        $t->setUrl('http://example.com/sub/page2');
        self::checkResponse($t->doTrackPageView('Viewing homepage2'));
    }
}