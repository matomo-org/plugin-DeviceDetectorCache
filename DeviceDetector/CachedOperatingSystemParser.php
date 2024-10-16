<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DeviceDetectorCache\DeviceDetector;

use DeviceDetector\Parser\OperatingSystem;

class CachedOperatingSystemParser extends OperatingSystem
{
    protected $cachedUaResult = [];

    public function setCachedResult($result)
    {
        $this->cachedUaResult = $result;
    }

    protected function parseOsFromUserAgent(): array
    {
        if (!empty($this->cachedUaResult)) {
            return $this->cachedUaResult;
        }

        return parent::parseOsFromUserAgent();
    }
}
