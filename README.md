# Matomo DeviceDetectorCache Plugin

## Description

Makes device detection in Matomo faster by having cached entries for many commonly used user agents.

By default, Matomo runs thousands of regular expressions for each tracking request to detect what Browser, Device, Operating system, ... is being used and to detect if a user agent is a bot or not.

This plugin changes this by first looking if a cached result exists for the particular user agent and if so, directly loads the result from the file system.

We recommend this plugin only if you have a high traffic website. Depending on your server it may safe you a few ms per tracking request (say 5ms which may be say 10% of the total tracking request time).

If you have not that much traffic, the overhead might not be worth it.

Note: We are caching here the user agents that are commonly used on our website. Depending on your target group the used user agents may differ and benefit less from this cache.

## Developer

### Caching user agents

./console device-detector-cache:warmup file1.csv

Where file1.csv is a CSV file that contains the user agent in the first column.

By default all user agents will be added to the list of user agents unless you pass the option `--clear` which removes
all previously cached user agents.

Note: If you add user agents to the cache yourself, they will be overwritten the next time you update the plugin.

Please don't create issues or pull requests regarding caching specific user agents. We will frequently clear the list of cached user agents and add new ones.

## Credits

* [Device Detector library my Matomo](https://github.com/matomo-org/device-detector/)
* [Matomo - Open Source Web Analytics](https://matomo.org)