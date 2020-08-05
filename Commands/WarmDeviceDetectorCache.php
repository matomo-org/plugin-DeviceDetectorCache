<?php
namespace Piwik\Plugins\DeviceDetectorCache\Commands;

use Piwik\Container\StaticContainer;
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
            $output->writeln("Count: " . $count . ' Mem:' . $mem . 'MB');
        }
    }

    private function log($message, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $mem = round(memory_get_peak_usage() / 1024 / 1024);
            $output->writeln($message . ' Mem:' . $mem . 'MB');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userAgents = array();

        $regex = $this->config->getAccessLogRegex();
        $numEntriesToCache = $this->config->getNumEntriesToCache();
        $matchEntry = $this->config->getRegexMatchEntry();
        $path = $this->config->getAccessLogPath();

        if (!file_exists($path)) {
            throw new \Exception('Configured access log path does not exist: ' . $path);
        }

        $this->log('caching up to ' . $numEntriesToCache . ' entries', $output);
        $this->log('reading from file ' . $path, $output);
        $this->log('used regex ' . $regex . ' with index ' . $matchEntry, $output);

        $numLinesToProcess = 5000000;
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
                usleep(30); // slightly slow down disk usage to avoid running eg into some EBS limit
            }

            fclose($handle);
        } else {
            throw new \Exception('Error opening file. Maybe no read permission? Path: ' . $path);
        }

        $this->log("parsed file", $output);
        $this->printupdate($count, $output);

        arsort($userAgents, SORT_NATURAL);

        $this->printupdate($count, $output);
        $i = 0;

        if (empty($userAgents)) {
            $output->writeln('No user agents found');
            return;
        }

        $this->log("writing files", $output);
        CachedEntry::clearCacheDir();
        foreach ($userAgents as $agent => $val) {
            if ($i >= $numEntriesToCache) {
                break;
            }
            $this->log("writing files", $output);
            $i++;
            if ($i % 10000 === 0) {
                $this->printupdate($i, $output);
            }
            if ($i <= 10) {
                $this->log('Found user agent '. $agent . ' count: '. $val, $output);
            }
            CachedEntry::writeToCache($agent);
        }
        $output->writeln('Written '.$i.' cache entries to file');
    }

}
