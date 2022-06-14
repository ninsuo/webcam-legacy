<?php

namespace AppBundle\Command;

use BaseBundle\Base\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('cron:archive')
            ->setDescription('Run the daily archivage')
            ->addOption('camera', null, InputOption::VALUE_REQUIRED, 'Only the specified camera')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize the camera service first in order to execute its constructor
        $camera = $input->getOption('camera', null);
        if (!$this->get('app.camera')->isCameraOrNull($camera)) {
            throw new \RuntimeException(sprintf('Camera %s not found.', $camera));
        }

        // Only getting timestamp for yesterday's images (Paris time)
        date_default_timezone_set($this->getParameter('timezone'));
        $today = strtotime(date("Y-m-d 00:00:00", time())) - 1;
        $yesterday = ($today - 24 * 60 * 60);

        // Browsing all webcams
        foreach ($this->get('app.camera')->getAvailableCameras() as $name) {
            if (!is_null($camera) && $camera !== $name) {
                continue ;
            }

            $directory = realpath(sprintf('%s/%s', $this->getParameter('webcam_path'), $name));

            if (!$directory) {
                throw new \RuntimeException(sprintf('Unable to find %s\'s realpath.', $directory));
            }

            chdir($directory);

            // Remove archives older than 30 days
            exec(sprintf('find %s/*.tar.gz -mtime +30 -exec rm {} \; 2>&1 > /dev/null', $directory));

            $from = date('Y-m-d H:i:s', $yesterday);
            $to = date('Y-m-d H:i:s', $today);
            $archive = sprintf('%s/webcam_%s', $directory, date('Y-m-d', $yesterday + 1));

            exec(sprintf('find . -newermt "%s" -not -newermt "%s" -regex ".*\.\(jpg\|png\|jpeg\)" -print0 | tar czf %s.tar.gz --atime-preserve --null -T -', $from, $to, basename($archive)));

            exec(sprintf('find . -newermt "%s" -not -newermt "%s" -regex ".*\.\(jpg\|png\|jpeg\)" -exec rm -f {} \;', $from, $to));
        }

        return 0;
    }
}
