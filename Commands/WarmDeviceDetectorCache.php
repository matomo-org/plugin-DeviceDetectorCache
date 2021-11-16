<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\DeviceDetectorCache\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\DeviceDetectorCache\CachedEntry;
use Piwik\Plugins\DeviceDetectorCache\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WarmDeviceDetectorCache extends ConsoleCommand
{
    const COMMAND_NAME = 'device-detector-cache:warm-cache';
    /**
     * @var Configuration
     */
    private $config;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->config = StaticContainer::get(Configuration::class);
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Cached device detector information based on access log');
    }

    private function printupdate($count, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $mem = round(memory_get_peak_usage() / 1024 / 1024);
            $now = Date::now()->getDatetime();
            $output->writeln("Count: " . $count . ' Mem:' . $mem . 'MB Date: ' . $now);
        }
    }

    private function log($message, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $mem = round(memory_get_peak_usage() / 1024 / 1024);
            $now = Date::now()->getDatetime();
            $output->writeln($message . ' Mem:' . $mem . 'MB Date: ' . $now);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userAgents = array();

        $regex = $this->config->getAccessLogRegex();
        $numEntriesToCache = $this->config->getNumEntriesToCache();
        $matchEntry = $this->config->getRegexMatchEntry();
        $path = $this->config->getAccessLogPath();
        $path = trim($path);

        $this->log('caching up to ' . $numEntriesToCache . ' entries', $output);
        $this->log('reading from file ' . $path, $output);
        $this->log('used regex ' . $regex . ' with index ' . $matchEntry, $output);

        if (empty($numEntriesToCache)) {
            $output->writeln('No entries are supposed to be cached. Stopping command');
            return 0;
        }

        if (!file_exists($path)) {
            throw new \Exception('Configured access log path does not exist: "' . $path . '"');
        }

        $count = 0;
        $numLinesToProcess = StaticContainer::get('DeviceDetectorCacheNumLinesToScan');
        $numLinesProcessed = 0;
        $handle = fopen($path, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $numLinesProcessed++;
                if ($numLinesProcessed >= $numLinesToProcess) {
                    break;// we read max 5M lines to prevent in running for too long time
                }
                if (empty($line)) {
                    continue;
                }
                preg_match($regex ,$line, $matches);
                if (!empty($matches[$matchEntry])
                    && strlen($matches[$matchEntry]) > 5
                    && strlen($matches[$matchEntry]) < 700){
                    $useragent = $matches[$matchEntry];
                    if (!isset($userAgents[$useragent])) {
                        $userAgents[$useragent] = 1;
                        $count = count($userAgents);
                        if ($count % 10000 === 0) {
                            $this->printupdate($count, $output);
                        }
                    } else {
                        $userAgents[$useragent] = $userAgents[$useragent] + 1;
                    }
                }
                $line = null;unset($line);
                $matches = null;unset($matches);
                if ($numLinesProcessed % 10 === 0) {
                    usleep(300); // slightly slow down disk usage to avoid running eg into some EBS limit
                }

                if ($numLinesProcessed % 1000 === 0) {
                    usleep(10000); // every 10K lines sleep for a 10ms to not max out CPU as much
                }
            }

            fclose($handle);
        } else {
            throw new \Exception('Error opening file. Maybe no read permission? Path: ' . $path);
        }

        $this->log("parsed file: " . $numLinesProcessed . " lines", $output);
        $this->printupdate($count, $output);

        arsort($userAgents, SORT_NATURAL);

        if (empty($userAgents)) {
            $output->writeln('No user agents found');
            return 0;
        }

        $this->log($count . ' user agents found', $output);
        $this->log("writing files", $output);

        $i = 0;
        $numRequestsDetected = 0;
        $ignoreUserAgentsWithLessRequestsThan = StaticContainer::get('DeviceDetectorCacheIgnoreUserAgentsWithLessThanXRequests');

        foreach ($userAgents as $agent => $val) {
            if ($i >= $numEntriesToCache) {
                $output->writeln('stopping because number of configured entries were cached');
                break;
            }
            if ($val < $ignoreUserAgentsWithLessRequestsThan) {
                $output->writeln('stopping because remaining user agents have only few requests');
                // we don't cache user agents that happened less than 9 times or less as it's so rare it's not really worth caching it and we rather do it on demand
                break;
            }
            $i++;
            $numRequestsDetected += $val; // useful to detect hit ratio

            if ($i % 5000 === 0) {
                $this->printupdate('written files so far: ' . $i . ' detecting that many requests: ' . $numRequestsDetected, $output);
            }
            if ($i <= 10) {
                $this->log('Found user agent '. $agent . ' count: '. $val, $output);
            }
            CachedEntry::writeToCache($agent);
            // sleep 2ms to let CPU do something else
            // this will make things about 10m slower for 200K entries but at least sudden CPU increase for instance
            // can be prevented when there are only few CPUs available
            // note: roughly per minute we write around 5K entries
            usleep(2000);
        }
        $output->writeln('Written '.$i.' cache entries to file.');
        $output->writeln('The hit ratio will be roughly ' . Piwik::getPercentageSafe($numRequestsDetected, $numLinesToProcess) . '%');

        $numCacheFilesExist = CachedEntry::getNumEntriesInCacheDir();
        $output->writeln($numCacheFilesExist . ' cached files exist');

        if ($numCacheFilesExist > $numEntriesToCache) {
            $numEntriesToDelete = $numCacheFilesExist - $numEntriesToCache;
            $output->writeln('Need to delete ' . $numEntriesToDelete . ' files');
            CachedEntry::deleteLeastAccessedFiles($numEntriesToDelete);
            $output->writeln('done deleting files');
        }

        return 0;
    }

}
