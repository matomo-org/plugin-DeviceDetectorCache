<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DeviceDetectorCache\Commands;

use Piwik\DeviceDetector\DeviceDetectorFactory;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCache;
use Piwik\Plugins\DeviceDetectorCache\DeviceDetectorCacheEntry;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WarmDeviceDetectorCache extends ConsoleCommand
{
    const COMMAND_NAME = 'device-detector-cache:warmup';

    const OPTION_INPUT_FILE = 'input-file';
    const OPTION_SKIP_HEADER = 'skip-header-row';
    const OPTION_ROWS_TO_PROCESS = 'count';
    const OPTION_CLEAR_EXISTING_CACHE = 'clear';

    private static $userAgentsPatternsToIgnore = array(
        '/Amazon-Route53-Health-Check-Service[.]*/'
    );

    private $numCacheEntriesWritten = 0;

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(
            'Populate the device detector cache with commonly used useragent strings, as provided in the input file.');
        $this->addArgument(self::OPTION_INPUT_FILE, InputArgument::REQUIRED, 
            'CSV file containing list of useragents to include');
        $this->addOption(
            self::OPTION_ROWS_TO_PROCESS,
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of rows to process',
            0
        );
        $this->addOption(
            self::OPTION_SKIP_HEADER,
            null,
            InputOption::VALUE_OPTIONAL,
            'Whether to skip the first row',
            true);
        $this->addOption(
            self::OPTION_CLEAR_EXISTING_CACHE,
            null,
            InputOption::VALUE_NONE,
            'Whether to clear existing entries from the cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::OPTION_CLEAR_EXISTING_CACHE)) {
            DeviceDetectorCacheEntry::clearCacheDir();
        }

        $inputFile = $this->openFile(
            $input->getArgument(self::OPTION_INPUT_FILE), 
            $input->getOption(self::OPTION_SKIP_HEADER)
        );

        $maxRowsToProcess = (int)$input->getOption(self::OPTION_ROWS_TO_PROCESS);
        $counter = 0;

        try {
            while ($data = fgetcsv($inputFile)) {
                $counter++;
                if ($maxRowsToProcess > 0 && $counter > $maxRowsToProcess) {
                    break;
                }

                $this->processUserAgent($data[0]);
            }
        } finally {
            fclose($inputFile);
        }
        $output->writeln("Written " . $this->numCacheEntriesWritten . " cache entries to file");
    }

    private function openFile($filePath, $skipFirstRow)
    {
        if (! file_exists($filePath)) {
            throw new \Exception("File $filePath not found");
        }
        $inputHandle = fopen($filePath, 'r');
        if ($inputHandle === false) {
            throw new \Exception("Could not open $filePath");
        }

        // Skip the first row
        if ($skipFirstRow) {
            fgetcsv($inputHandle);
        }
        return $inputHandle;
    }

    private function isValidUserAgentString($userAgent)
    {
        $matches = array();
        foreach (self::$userAgentsPatternsToIgnore as $pattern) {
            preg_match($pattern, $userAgent,   $matches);
            if ($matches) {
                return false;
            }
        }

        $parts = explode($userAgent, ' ');
        foreach ($parts as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP) !== false) {
                return false;
            }
        }

        return true;
    }

    private function processUserAgent($userAgentStr)
    {
        $userAgentStr = trim(trim($userAgentStr, '"'));
        if (!$this->isValidUserAgentString($userAgentStr)) {
            return;
        }

        $factory = new DeviceDetectorFactory();
        $deviceDetector = $factory->makeInstance($userAgentStr);

        DeviceDetectorCache::writeToCache($userAgentStr, $deviceDetector);
        $this->numCacheEntriesWritten++;
    }
}