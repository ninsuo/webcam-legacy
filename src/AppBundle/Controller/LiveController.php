<?php

namespace AppBundle\Controller;

use BaseBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LiveController extends BaseController
{
    /**
     * @Security("has_role('ROLE_USER')")
     * @Route("/live/{name}", name="live")
     * @Template()
     */
    public function indexAction($name)
    {
        // TODO
    }
}
