# Matomo DeviceDetectorCache Plugin

## Description

Makes device detection in Matomo faster by having cached entries for many commonly used user agents.

By default, Matomo runs thousands of regular expressions for each tracking request to detect what Browser, Device, Operating system, ... is being used and to detect if a user agent is a bot or not.

This plugin changes this by first looking if a cached result exists for the particular user agent and if so, directly loads the result from the file system.

We recommend this plugin only if you have a high traffic website. Depending on your server it may safe you a few ms per tracking request (say 5ms which may be say 10% of the total tracking request time).

If you have not that much traffic, the overhead might not be worth it.

Note: We are caching here the user agents that are commonly used on our website. Depending on your target group the used user agents may differ and benefit less from this cache.