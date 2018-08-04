<?php

namespace AppBundle\Command;

use BaseBundle\Base\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('cron:archive')
            ->setDescription('Run the daily archivage')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Only getting timestamp for yesterday's images (Paris time)
        date_default_timezone_set($this->getParameter('timezone'));
        $today = strtotime(date("Y-m-d 00:00:00", time())) - 1;
        $yesterday = ($today - 24 * 60 * 60 + 1);

        // Browsing all webcams
        foreach ($this->get('app.camera')->getAvailableCameras() as $name) {
            $directory = realpath(sprintf('%s/%s', $this->getParameter('webcam_path'), $name));

            if (!$directory) {
                throw new \RuntimeException(sprintf('Unable to find %s\'s realpath.', $directory));
            }

            chdir($directory);

            // Remove archives older than 30 days
            exec(sprintf('find %s/*.tar.gz -mtime +30 -exec rm {} \; 2>&1 > /dev/null', $directory));

            // Recovering all yesterday's images
            $files = glob(sprintf('%s/*.jpg', $directory));
            $toArchive = array();
            foreach ($files as $file)
            {
                $stat = stat($file);
                if (($stat['mtime'] >= $yesterday) && ($stat['mtime'] <= $today))
                {
                    $toArchive[] = $file;
                }
            }
            if (count($toArchive) == 0) {
                continue ;
            }

            // Rename and archive yesterday's images
            $archive = sprintf('%s/webcam_%s', $directory, date('Y-m-d', $yesterday));
            mkdir($archive);
            foreach ($toArchive as $file)
            {
                $source = escapeshellarg(basename($file));
                $target = sprintf('%s.%s', date('Y-m-d_H-i-s', filemtime($file)), pathinfo($file, PATHINFO_EXTENSION));
                exec('mv %s %s/%s', $source, $archive, $target);
            }

            exec('tar czf %s.tgz --atime-preserve %s', basename($archive), basename($archive));
            exec("rm -rf %s", $archive);

            // Remove yesterday's images
            foreach ($toArchive as $file)
            {
                unlink($file);
            }
        }

        return 0;
    }
}
