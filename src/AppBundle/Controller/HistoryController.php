<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormInterface;
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
        $filters = $this->createFilterForm();

        return [
            'pager' => $this->getPager($this->get('app.camera')->listImages($name)),
            'name'  => $name,
        ];
    }

    /**
     * @Route("/file/{name}/{size}-{file}", name="file")
     * @Template()
     */
    public function fileAction($name, $file, $size)
    {
        return new Response($this->get('app.camera')->getImageByFilename($name, $file, $size), 200, [
            'Content-Type'     => 'image/jpeg',
            'Pragma-Directive' => 'no-cache',
            'Cache-Directive'  => 'no-cache',
            'Cache-Control'    => 'no-cache',
            'Pragma'           => 'no-cache',
            'Expires'          => '0',
        ]);
    }

    private function createFilterForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->add('from', TimeType::class, [
                'input'         => 'timestamp',
                'view_timezone' => 'UTC',
                'with_seconds'  => true,
                'widget'        => 'single_text',
            ])
            ->add('to', TimeType::class, [
                'input'         => 'timestamp',
                'view_timezone' => 'UTC',
                'with_seconds'  => true,
                'widget'        => 'single_text',
            ])
            ->add('step', NumberType::class)
            ->getForm();
    }
}
