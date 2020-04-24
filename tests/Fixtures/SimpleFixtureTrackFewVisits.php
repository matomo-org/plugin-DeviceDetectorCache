<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\DeviceDetectorCache\tests\Fixtures;

use Piwik\Date;
use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCache;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCacheEntry;
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

    private $revertFileAfter = false;

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
        $device2CacheFile = DeviceDetectorCacheEntry::getCachePath($this->userAgent2);

        if (file_exists($device2CacheFile)) {
            $this->revertFileAfter = true;
        }

        $factory = new DeviceDetectorFactory();
        $device1 = $factory->makeInstance($this->userAgent1);
        DeviceDetectorCache::writeToCache($this->userAgent2, $device1);
    }

    public function tearDown(): void
    {
        // empty
        if ($this->revertFileAfter) {
            $factory = new DeviceDetectorFactory();
            $device2 = $factory->makeInstance($this->userAgent1);
            DeviceDetectorCache::writeToCache($this->userAgent2, $device2);
        }
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
        // We track with useragent2 (Windows) but expect to see data for useragent1 (Apple)
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
    }
}