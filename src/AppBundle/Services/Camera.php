<?php

namespace AppBundle\Services;

use BaseBundle\Base\BaseService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Camera extends BaseService
{
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
        $no    = intval($count * $value / 10000);
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
        date_default_timezone_set('UTC');

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