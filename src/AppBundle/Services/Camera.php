<?php

namespace AppBundle\Services;

use BaseBundle\Base\BaseService;

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

    public function getLastImage($name)
    {
        if (!preg_match('/^[0-9a-zA-Z]+$/', $name)) {
            return $this->createErrorImage();
        }

        $dir = sprintf('%s/%s', $this->getParameter('webcam_path'), $name);
        if (!is_dir($dir) || !is_readable($dir)) {
            return $this->createErrorImage();
        }

        $file = sprintf('%s/%s', $dir, exec(sprintf('ls -r %s/|grep -i jpg|head -1', $dir)));
        if (!is_readable($file)) {
            return $this->createErrorImage();
        }

        return [
            'filename' => $file,
            'content'  => file_get_contents($file),
        ];
    }

    public function createErrorImage()
    {
        $img = imagecreate(1280, 720);
        ob_start();
        imagepng($img);

        return [
            'filename' => null,
            'content'  => ob_get_clean(),
        ];
    }
}