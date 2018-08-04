<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @Security("has_role('ROLE_USER')")
 */
class HistoryController extends BaseController
{
    /**
     * @Route("/reader/{name}", name="reader")
     * @Template()
     */
    public function indexAction(Request $request, $name)
    {
        $images  = $this->get('app.camera')->listImages($name);
        $filters = $this->createFilterForm($request, $images);
        $images  = $this->applyFiltersOnImages($images, $filters);

        return [
            'pager'   => $this->getPager($images),
            'name'    => $name,
            'filters' => $filters->createView(),
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

    /**
     * @Route("/details/{name}/{file}", name="details")
     * @Template()
     */
    public function detailsAction(Request $request, $name, $file)
    {
        $images  = $this->get('app.camera')->listImages($name);
        $filters = $this->createFilterForm($request, $images);
        $images  = array_values($this->applyFiltersOnImages($images, $filters));

        $previous = null;
        $current = null;
        $next = null;
        foreach ($images as $key => $image) {
            if ($image['file'] == $file) {
                if ($key != 0) {
                    $previous = $images[$key - 1];
                }
                $current = $images[$key];
                if ($key + 1 != count($images)) {
                    $next = $images[$key + 1];
                }
            }
        }

        return [
            'name' => $name,
            'filters' => $filters->createView(),
            'previous' => $previous,
            'current' => $current,
            'next' => $next,
        ];
    }

    private function createFilterForm(Request $request, $images): FormInterface
    {
        $data = [
            'from' => reset($images)['time'],
            'to'   => end($images)['time'],
            'step' => 1,
        ];

        return $this->createNamedFormBuilder('filter', FormType::class, $data, ['csrf_protection' => false])
            ->setMethod('GET')
            ->add('from', TimeType::class, [
                'input'          => 'timestamp',
                'view_timezone'  => 'UTC',
                'with_seconds'   => true,
                'widget'         => 'single_text',
                'error_bubbling' => true,
                'constraints'    => [
                    new NotBlank(),
                    new Range(['min' => 0, 'max' => 86400]),
                ],
            ])
            ->add('to', TimeType::class, [
                'input'          => 'timestamp',
                'view_timezone'  => 'UTC',
                'with_seconds'   => true,
                'widget'         => 'single_text',
                'error_bubbling' => true,
                'constraints'    => [
                    new NotBlank(),
                    new Range(['min' => 0, 'max' => 86400]),
                ],
            ])
            ->add('step', NumberType::class, [
                'error_bubbling' => true,
                'constraints'    => [
                    new NotBlank(),
                    new Range(['min' => 1]),
                ],
            ])
            ->add('go', SubmitType::class, [
                'label' => 'Go',
            ])
            ->getForm()
            ->handleRequest($request);
    }

    private function applyFiltersOnImages($images, $filters)
    {
        if (!$filters->isSubmitted() || !$filters->isValid()) {
            return $images;
        }

        $data = $filters->getData();

        return array_filter($images, function ($value, $key) use ($data) {
            if ($key % $data['step'] !== 0) {
                return false;
            }
            if ($data['from'] > $data['to'] && $value['time'] < $data['from'] && $value['time'] > $data['to']) {
                return false;
            } elseif ($data['from'] < $data['to'] && ($value['time'] < $data['from'] || $value['time'] > $data['to'])) {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
