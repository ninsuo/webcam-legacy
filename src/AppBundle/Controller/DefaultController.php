<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * @Route("/", name="home")
     * @Template()
     */
    public function indexAction()
    {
        return [
            'cameras' => $this->get('app.camera')->getAvailableCameras(),
        ];
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/live/{name}", name="live")
     * @Template()
     */
    public function liveAction($name)
    {
        return [
            'name' => $name,
        ];
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/last/{name}.jpg", name="last")
     * @Template()
     */
    public function lastAction($name)
    {
        return new Response($this->get('app.camera')->getLastImage($name), 200, [
            'Content-Type' => 'image/jpeg',
            'Pragma-Directive: no-cache',
            'Cache-Directive: no-cache',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Expires: 0',
        ]);
    }
}
