<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("has_role('ROLE_USER')")
 */
class LiveController extends BaseController
{
    /**
     * @Route("/live/{name}", name="live")
     * @Template()
     */
    public function indexAction($name)
    {
        return [
            'name'     => $name,
            'archives' => $this->get('app.camera')->getArchives($name),
        ];
    }

    /**
     * @Route("/history/{name}.jpg", name="history")
     * @Template()
     */
    public function historyAction(Request $request, $name)
    {
        return new Response($this->get('app.camera')->getImage($name, $request->query->get('val')), 200, [
            'Content-Type'     => 'image/jpeg',
            'Pragma-Directive' => 'no-cache',
            'Cache-Directive'  => 'no-cache',
            'Cache-Control'    => 'no-cache',
            'Pragma'           => 'no-cache',
            'Expires'          => '0',
        ]);
    }

    /**
     * @Route("/archive/{name}/{filename}", name="archive")
     * @Template()
     */
    public function archiveAction($name, $filename)
    {
        return new BinaryFileResponse(
            new Stream(
                $this->get('app.camera')->getArchive($name, $filename)
            )
        );
    }

    /**
     * @Route("/refresh/{name}", name="refresh")
     * @Template()
     */
    public function refreshAction($name)
    {
        return [
            'name' => $name,
        ];
    }
}