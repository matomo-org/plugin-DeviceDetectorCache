# Matomo DeviceDetectorCache Plugin

[![Build Status](https://travis-ci.org/matomo-org/plugin-DeviceDetectorCache.svg?branch=master)](https://travis-ci.org/matomo-org/plugin-DeviceDetectorCache)

## Description

Makes device detection in Matomo faster by having cached entries for many commonly used user agents.

By default, Matomo runs thousands of regular expressions for each tracking request to detect what Browser, Device, Operating system, ... is being used and to detect if a user agent is a bot or not.

This plugin changes this by first looking if a cached result exists for the particular user agent and if so, directly loads the result from the file system.

We recommend this plugin only if you have a very high traffic website (> 200M requests a month). Depending on your server it may save you a few ms per tracking request (say 5ms which may be say 10% of the total tracking request time).

If you have not that much traffic, the overhead might not be worth it.

### How to set it up

#### Config setup

Configure these values in your `config/config.ini.php`

```
[DeviceDetectorCache]
access_log_path = "/var/log/httpd/access_log" # The path to your access log file. This command needs to have read permission for this file
access_log_regex = "/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) "([^"]*)" "([^"]*)"$/" # the regex used to extract the user agent
regex_match_entry = 14 # defines which subpattern of the abovce regex matches the user agent
num_cache_entries = 200000 # how many user agents should be cached. This value basically depends on your memory and disk space. Likely there is no need to change this
```

#### Testing if it works

Run this command to see if it works:

```
php /path/to/matomo/console device-detector-cache:warm-cache -vvv
```

It should show how many user agents were detected and should print the top 10 most commonly found user agents if things are configured correctly. 

Cached files will be stored in `/tmp/devicecache/`. Make sure there is write access for this folder. Every time this command runs previously created cache entries will be deleted.

#### Set up a cronjob

If above test goes well you need to set up a cronjob that runs regularly (eg every few hours or days) to update the cached entries based on the access log.

The cronjob needs to look like for example like this:

```
0 8 * * * php /path/to/matomo/console device-detector-cache:warm-cache
```

If you have multiple servers, you need to set up the command on every server that processes tracking requests.

## Credits

* [PHP Device Detector library](https://github.com/matomo-org/device-detector/)
* [Matomo - Open Source Web Analytics](https://matomo.org)
