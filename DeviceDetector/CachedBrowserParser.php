<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DeviceDetectorCache\DeviceDetector;

use DeviceDetector\Parser\Client\Browser;

class CachedBrowserParser extends Browser
{
    protected $cachedUaResult = [];

    public function setCachedResult($result)
    {
        $this->cachedUaResult = $result;
    }

    protected function parseBrowserFromUserAgent(): array
    {
        if (!empty($this->cachedUaResult)) {
            return $this->cachedUaResult;
        }

        return parent::parseBrowserFromUserAgent();
    }
}