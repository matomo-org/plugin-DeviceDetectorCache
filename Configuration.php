<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache;

use Piwik\Config;

class Configuration
{
    public const KEY_NUM_ENTRIES_TO_CACHE = 'num_cache_entries';
    public const DEFAULT_NUM_ENTRIES_TO_CACHE = 200000;

    public const KEY_ACCESS_LOG_REGEX = 'access_log_regex';
    public const DEFAULT_ACCESS_LOG_REGEX = '/^(\S+) (\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) "([^"]*)" "([^"]*)" (\d+)$/';

    public const KEY_ACCESS_LOG_REGEX_MATCH_ENTRY = 'regex_match_entry';
    public const DEFAULT_ACCESS_LOG_REGEX_MATCH_ENTRY = 14;

    public const KEY_ACCESS_LOG_PATH = 'access_log_path';
    public const DEFAULT_ACCESS_LOG_PATH = '/var/log/httpd/access_log';

    public function install()
    {
        $config = $this->getConfig();

        if (empty($config->DeviceDetectorCache)) {
            $config->DeviceDetectorCache = [];
        }

        $cache = $config->DeviceDetectorCache;

        // we make sure to set a value only if none has been configured yet, eg in common config.
        if (empty($cache[self::KEY_NUM_ENTRIES_TO_CACHE])) {
            $cache[self::KEY_NUM_ENTRIES_TO_CACHE] = self::DEFAULT_NUM_ENTRIES_TO_CACHE;
        }
        if (empty($cache[self::KEY_ACCESS_LOG_PATH])) {
            $cache[self::KEY_ACCESS_LOG_PATH] = self::DEFAULT_ACCESS_LOG_PATH;
        }
        if (empty($cache[self::KEY_ACCESS_LOG_REGEX])) {
            $cache[self::KEY_ACCESS_LOG_REGEX] = self::DEFAULT_ACCESS_LOG_REGEX;
        }
        if (empty($cache[self::KEY_ACCESS_LOG_REGEX_MATCH_ENTRY])) {
            $cache[self::KEY_ACCESS_LOG_REGEX_MATCH_ENTRY] = self::DEFAULT_ACCESS_LOG_REGEX_MATCH_ENTRY;
        }

        $config->DeviceDetectorCache = $cache;

        $config->forceSave();
    }

    public function uninstall()
    {
        $config                      = $this->getConfig();
        $config->DeviceDetectorCache = [];
        $config->forceSave();
    }

    /**
     * @return string
     */
    public function getAccessLogPath()
    {
        return $this->getConfigValue(self::KEY_ACCESS_LOG_PATH, self::DEFAULT_ACCESS_LOG_PATH);
    }

    /**
     * @return string
     */
    public function getAccessLogRegex()
    {
        return $this->getConfigValue(self::KEY_ACCESS_LOG_REGEX, self::DEFAULT_ACCESS_LOG_REGEX);
    }

    /**
     * @return string
     */
    public function getRegexMatchEntry()
    {
        return (int)$this->getConfigValue(self::KEY_ACCESS_LOG_REGEX_MATCH_ENTRY, self::DEFAULT_ACCESS_LOG_REGEX_MATCH_ENTRY);
    }

    /**
     * @return string
     */
    public function getNumEntriesToCache()
    {
        return (int)$this->getConfigValue(self::KEY_NUM_ENTRIES_TO_CACHE, self::DEFAULT_NUM_ENTRIES_TO_CACHE);
    }

    private function getConfig()
    {
        return Config::getInstance();
    }

    private function getConfigValue($name, $default)
    {
        $config      = $this->getConfig();
        $attribution = $config->DeviceDetectorCache;
        if (isset($attribution[$name])) {
            return $attribution[$name];
        }
        return $default;
    }
}
