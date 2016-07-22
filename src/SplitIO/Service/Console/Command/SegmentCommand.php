<?php
namespace SplitIO\Service\Console\Command;

use SplitIO\Component\Cache\SegmentCache;
use SplitIO\Component\Stats\Latency;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SegmentCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('process:fetch-segments')
            ->setDescription('Fetch Segment keys from server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $registeredSegments = $this->cache()->getItemsOnList(SegmentCache::getCacheKeyForRegisterSegments());

        if (is_array($registeredSegments) && !empty($registeredSegments)) {
            foreach ($registeredSegments as $segmentName) {

                $this->logger()->info(">>> Fetching data from segment: $segmentName");
                $timeStart = Latency::startMeasuringLatency();
                while (true) {
                    $timeStartPart = Latency::startMeasuringLatency();
                    if (! $this->getSplitClient()->updateSegmentChanges($segmentName)) {

                        $timeItTook = Latency::calculateLatency($timeStartPart);
                        $this->logger()->debug("Fetching segment last part ($segmentName) took $timeItTook microseconds");
                        $greedyTime = Latency::calculateLatency($timeStart);
                        $this->logger()->info("Finished fetching whole segment $segmentName, took $greedyTime microseconds");
                        break;
                    }

                    $timeItTook = Latency::calculateLatency($timeStartPart);
                    $this->logger()->debug("Fetching segment part ($segmentName) took $timeItTook microseconds");

                    //Sleep 1/2 second
                    usleep(500000);
                }

            }
        }
    }
}