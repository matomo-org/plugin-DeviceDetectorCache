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

namespace Piwik\Plugins\DeviceDetectorCache;

use Piwik\Config;

class Configuration
{
    const KEY_NumEntriesToCache = 'num_cache_entries';
    const DEFAULT_NumEntriesToCache = 200000;

    const KEY_AccessLogRegex = 'access_log_regex';
    const DEFAULT_AccessLogRegex = '/^(\S+) (\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) "([^"]*)" "([^"]*)" (\d+)$/';

    const KEY_AccessLogRegexMatchEntry = 'regex_match_entry';
    const DEFAULT_AccessLogRegexMatchEntry = 14;

    const KEY_AccessLogPath = 'access_log_path';
    const DEFAULT_AccessLogPath = '/var/log/httpd/access_log';

    public function install()
    {
        $config = $this->getConfig();

        if (empty($config->DeviceDetectorCache)) {
            $config->DeviceDetectorCache = array();
        }

        $cache = $config->DeviceDetectorCache;

        // we make sure to set a value only if none has been configured yet, eg in common config.
        if (empty($cache[self::KEY_NumEntriesToCache])) {
            $cache[self::KEY_NumEntriesToCache] = self::DEFAULT_NumEntriesToCache;
        }
        if (empty($cache[self::KEY_AccessLogPath])) {
            $cache[self::KEY_AccessLogPath] = self::DEFAULT_AccessLogPath;
        }
        if (empty($cache[self::KEY_AccessLogRegex])) {
            $cache[self::KEY_AccessLogRegex] = self::DEFAULT_AccessLogRegex;
        }
        if (empty($cache[self::KEY_AccessLogRegexMatchEntry])) {
            $cache[self::KEY_AccessLogRegexMatchEntry] = self::DEFAULT_AccessLogRegexMatchEntry;
        }

        $config->DeviceDetectorCache = $cache;

        $config->forceSave();
    }

    public function uninstall()
    {
        $config = $this->getConfig();
        $config->DeviceDetectorCache = array();
        $config->forceSave();
    }

    /**
     * @return string
     */
    public function getAccessLogPath()
    {
        return $this->getConfigValue(self::KEY_AccessLogPath, self::DEFAULT_AccessLogPath);
    }

    /**
     * @return string
     */
    public function getAccessLogRegex()
    {
        return $this->getConfigValue(self::KEY_AccessLogRegex, self::DEFAULT_AccessLogRegex);
    }

    /**
     * @return string
     */
    public function getRegexMatchEntry()
    {
        return (int) $this->getConfigValue(self::KEY_AccessLogRegexMatchEntry, self::DEFAULT_AccessLogRegexMatchEntry);
    }

    /**
     * @return string
     */
    public function getNumEntriesToCache()
    {
        return (int) $this->getConfigValue(self::KEY_NumEntriesToCache, self::DEFAULT_NumEntriesToCache);
    }

    private function getConfig()
    {
        return Config::getInstance();
    }

    private function getConfigValue($name, $default)
    {
        $config = $this->getConfig();
        $attribution = $config->DeviceDetectorCache;
        if (isset($attribution[$name])) {
            return $attribution[$name];
        }
        return $default;
    }
}
