<?php

namespace AppBundle\Services;

use BaseBundle\Base\BaseService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Camera extends BaseService
{
    const SLIDER = 10000;

    const SIZE_SMALL = 'small';
    const SIZE_LARGE = 'large';

    const DEFAULT_WIDTH     = 1280;
    const DEFAULT_HEIGHT    = 720;
    const DEFAULT_TIME_SIZE = 28;
    const DEFAULT_TIME_X    = 835;
    const DEFAULT_TIME_Y    = 700;

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

    public function getImageByNumber($name, $value, $size)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        $count = trim(exec(sprintf('ls -t %s|grep -i jpg|wc -l', $dir)));
        $no    = intval($count * $value / self::SLIDER) + 1;
        $exec  = sprintf("ls -tr %s|grep -i jpg|cat -n|egrep '^[ ]+%d\t'", $dir, $no);
        $file  = trim(exec($exec));

        if (!$file) {
            return $this->createErrorImage();
        }

        return $this->timestampize(sprintf('%s/%s', $dir, substr($file, strpos($file, "\t") + 1)), $size);
    }

    public function getImageByFilename($name, $file, $size)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        if (!preg_match('/^[0-9a-zA-Z\.\-_\(\)]+\.jpg$/', $file)) {
            return $this->createErrorImage();
        }

        return $this->timestampize(sprintf('%s/%s', $dir, $file), $size);
    }

    public function getLastImage($name, $size)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        $file = sprintf('%s/%s', $dir, exec(sprintf('ls -t %s/|grep -i jpg|head -1', $dir)));
        if (!is_readable($file)) {
            return $this->createErrorImage();
        }

        return $this->timestampize($file, $size);
    }

    public function timestampize($file, $size)
    {
        $img = @imagecreatefromjpeg($file);

        $ratioX = imagesx($img) / self::DEFAULT_WIDTH;
        $ratioY = imagesy($img) / self::DEFAULT_HEIGHT;

        imagettftext(
            $img, $ratioX * self::DEFAULT_TIME_SIZE, 0, $ratioX * self::DEFAULT_TIME_X, $ratioY * self::DEFAULT_TIME_Y,
            imagecolorallocate($img, 255, 255, 0),
            __DIR__.'/../Resources/fonts/Lato/Lato-Regular.ttf',
            date("d/m/Y H:i:s \U\T\C", filemtime($file))
        );

        if ($size === self::SIZE_SMALL) {
            $img = imagescale($img, imagesx($img) / 2, imagesy($img) / 2);
        }

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

        if (!preg_match('/^[0-9a-zA-Z\.\-_]+\.tar\.gz$/', $filename)) {
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

        $exec = sprintf("ls -Ahopt --time-style +\" %%s \" %s|cat -n|grep %d|cut -d ' ' -f 2|cut -d '\t' -f 1", $dir, $tm);
        $no   = exec($exec);
        if (!$no) {
            return ['no' => null, 'slider' => self::SLIDER];
        }

        $count = trim(exec(sprintf('ls -t %s|grep -i jpg|wc -l', $dir)));
        $value = intval($no * self::SLIDER / $count);

        return ['no' => $no, 'slider' => $value];
    }

    public function listImages($name)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            throw new NotFoundHttpException();
        }

        $data = [];
        foreach (array_map('basename', glob(sprintf('%s/*.jpg', $dir))) as $file) {
            $data[] = [
                'file' => $file,
                'time' => filemtime(sprintf('%s/%s', $dir, $file)),
            ];
        }

        return array_reverse($data);
    }

    private function checkDirectory($name)
    {
        if (!preg_match('/^[0-9a-zA-Z\-_\(\)]+$/', $name)) {
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
        $img = imagecreate(self::DEFAULT_WIDTH, self::DEFAULT_HEIGHT);
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }
}