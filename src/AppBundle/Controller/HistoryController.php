<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("has_role('ROLE_USER')")
 */
class HistoryController extends BaseController
{
    /**
     * @Route("/reader/{name}", name="reader")
     * @Template()
     */
    public function indexAction($name)
    {
        return [
            'pager' => $this->getPager($this->get('app.camera')->listImages($name)),
            'name'  => $name,
        ];
    }

    /**
     * @Route("/file/{name}/{file}", name="file")
     * @Template()
     */
    public function fileAction($name, $file)
    {
        return new Response($this->get('app.camera')->getImageByFilename($name, $file), 200, [
            'Content-Type'     => 'image/jpeg',
            'Pragma-Directive' => 'no-cache',
            'Cache-Directive'  => 'no-cache',
            'Cache-Control'    => 'no-cache',
            'Pragma'           => 'no-cache',
            'Expires'          => '0',
        ]);
    }
}
