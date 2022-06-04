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

    public function setRightTimezone()
    {
        date_default_timezone_set('UTC');
    }

    public function getAvailableCameras()
    {
        $cameras = [];

        foreach (glob($this->getParameter('webcam_path') . '/*') as $name) {
            if (is_dir($name)) {
                $cameras[] = basename($name);
            }
        }

        return $cameras;
    }

    public function isCameraOrNull($name)
    {
        return !$name || in_array($name, $this->getAvailableCameras());
    }

    public function getImageByNumber($name, $value, $size)
    {
        $dir = $this->checkDirectory($name);
        if (is_null($dir)) {
            return $this->createErrorImage();
        }

        $count = trim(exec(sprintf('ls -tR %s|grep -i jpg|wc -l', $dir)));
        $no    = intval($count * $value / self::SLIDER) + 1;
        $exec  = sprintf("ls -trR %s|grep -i jpg|cat -n|egrep '^[ ]+%d\t'", $dir, $no);
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

        $file = sprintf('%s/%s', $dir, exec(sprintf('ls -tR %s/|grep -i jpg|head -2|tac|head -1', $dir)));
        if (!is_readable($file)) {
            return $this->createErrorImage();
        }

        return $this->timestampize($file, $size);
    }

    public function timestampize($file, $size)
    {
        if (!$file || !is_file($file) || !is_readable($file)) {
            return $this->createErrorImage();
        }

        $img = @imagecreatefromjpeg($file);

        if (!$img) {
            return $this->createErrorImage();
        }

        $ratioX = imagesx($img) / self::DEFAULT_WIDTH;
        $ratioY = imagesy($img) / self::DEFAULT_HEIGHT;

        imagettftext(
            $img, $ratioX * self::DEFAULT_TIME_SIZE, 0, $ratioX * self::DEFAULT_TIME_X, $ratioY * self::DEFAULT_TIME_Y,
            imagecolorallocate($img, 255, 255, 0),
            __DIR__ . '/../Resources/fonts/Lato/Lato-Regular.ttf',
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

    public function getImageAt($name, $time)
    {
        $closest     = null;
        $closestTime = 0xFFFFFFFF;
        $images      = $this->listImages($name);
        foreach ($images as $image) {
            if (abs($image['time'] - $time) < $closestTime) {
                $closest     = $image;
                $closestTime = abs($image['time'] - $time);
            }
        }

        if (is_null($closest)) {
            return ['no' => null, 'slider' => self::SLIDER];
        }

        $no    = array_search($closest, $images);
        $value = intval($no * self::SLIDER / count($images));

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
            $date = filemtime(sprintf('%s/%s', $dir, $file));

            $data[] = [
                'file' => $file,
                'date' => strtotime(date('Y-m-d 00:00:00', $date)),
                'time' => $date % 86400,
            ];
        }

        usort($data, function ($a, $b) {
            return $a['date'] + $a['time'] > $b['date'] + $b['time'] ? 1 : -1;
        });

        return $data;
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