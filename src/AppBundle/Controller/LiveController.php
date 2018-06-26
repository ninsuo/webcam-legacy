<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Watcher;
use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            'search'   => $this->createSearchForm()->createView(),
        ];
    }

    /**
     * @Route("/search/{name}", name="search")
     * @Template()
     */
    public function searchAction(Request $request, $name)
    {
        $form = $this->createSearchForm()->handleRequest($request);

        $tm = time() - 1;
        if ($form->isValid()) {
            $tm = $form->getData()['time'];
        }

        return new JsonResponse(
            $this->get('app.camera')->getImageAt($name, $tm)
        );
    }

    /**
     * @Route("/history/{name}-{size}.jpg", name="history")
     * @Template()
     */
    public function historyAction(Request $request, $name, $size)
    {
        $this->watch($name, $size);

        return new Response($this->get('app.camera')->getImageByNumber($name, $request->query->get('val'), $size), 200, [
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
     * @Route("/activity/{name}", name="activity")
     * @Template()
     */
    public function activityAction($name)
    {
        return [
            'activities' => $this->getManager(Watcher::class)->getActivity($name),
        ];
    }

    private function createSearchForm()
    {
        return $this->createFormBuilder(['time' => time()])
            ->add('time', TimeType::class, [
                'input'         => 'timestamp',
                'view_timezone' => 'UTC',
                'with_seconds'  => true,
            ])
            ->getForm();
    }
}
