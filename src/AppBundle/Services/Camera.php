<?php

namespace AppBundle\Services;

use BaseBundle\Base\BaseService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Camera extends BaseService
{
    const SLIDER = 10000;

    public function __construct()
    {
        date_default_timezone_set('UTC');
    }

    public function getAvailableCameras()
    {
        $cameras = [];

        foreach (glob($this->getParameter('webcam_path').'/*') as $name) {
            if (is_dir($name)) {
                $cameras[] = basename($name);
            }
        }

        return $cameras;
    }

    public function getImage($name, $value)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        $count = trim(exec(sprintf('ls %s|grep -i jpg|wc -l', $dir)));
        $no    = intval($count * $value / self::SLIDER);
        $file  = exec(sprintf("ls %s|cat -n|grep '%d\t'|cut -d ' ' -f 2|cut -d '\t' -f 2", $dir, $no));

        return $this->timestampize(sprintf('%s/%s', $dir, $file));
    }

    public function getLastImage($name)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        $file = sprintf('%s/%s', $dir, exec(sprintf('ls -r %s/|grep -i jpg|head -1', $dir)));
        if (!is_readable($file)) {
            return $this->createErrorImage();
        }

        return $this->timestampize($file);
    }

    public function timestampize($file)
    {
        $img = imagecreatefromjpeg($file);
        imagettftext(
            $img, 28, 0, 840, 700,
            imagecolorallocate($img, 255, 255, 0),
            __DIR__.'/../Resources/fonts/Lato/Lato-Regular.ttf',
            date("d/m/Y H:i:s \U\T\C", filemtime($file))
        );

        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }

    public function getArchives($name)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return [];
        }

        $archives = [];
        foreach (glob(sprintf('%s/*.tar.gz', $dir)) ?? [] as $archive) {
            $archives[] = [
                'filename' => basename($archive),
                'size'     => filesize($archive),
            ];
        }

        return $archives;
    }

    public function getArchive($name, $filename)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            throw new NotFoundHttpException();
        }

        if (!preg_match('/^[0-9a-zA-Z\.\-_]+$/', $filename)) {
            throw new NotFoundHttpException();
        }

        $file = sprintf('%s/%s', $dir, $filename);
        if (!is_file($file) || !is_readable($file)) {
            throw new NotFoundHttpException();
        }

        return $file;
    }

    public function getImageAt($name, $tm)
    {
        $tm = strtotime(date("Y-m-d")) + $tm;

        $dir = $this->checkDirectory($name);
        if (!$dir) {
            return ['no' => null, 'slider' => self::SLIDER];
        }

        $exec = sprintf("ls -Ahop --time-style +\" %%s \" %s|cat -n|grep %d|cut -d ' ' -f 2|cut -d '\t' -f 1", $dir, $tm);
        $no   = exec($exec);
        if (!$no) {
            return [
                'exec'   => $exec,
                'no'     => null,
                'slider' => self::SLIDER,
            ];
        }

        $count = trim(exec(sprintf('ls %s|grep -i jpg|wc -l', $dir)));
        $value = intval($no * self::SLIDER / $count);

        return ['no' => $no, 'slider' => $value];
    }

    private function checkDirectory($name)
    {
        if (!preg_match('/^[0-9a-zA-Z]+$/', $name)) {
            return null;
        }

        $dir = sprintf('%s/%s', $this->getParameter('webcam_path'), $name);
        if (!is_dir($dir) || !is_readable($dir)) {
            return null;
        }

        return $dir;
    }

    private function createErrorImage()
    {
        $img = imagecreate(1280, 720);
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }
}