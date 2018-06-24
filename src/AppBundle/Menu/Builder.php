<?php

namespace AppBundle\Menu;

use BaseBundle\Base\BaseMenu;
use Knp\Menu\FactoryInterface;

class Builder extends BaseMenu
{
    public function mainLeftMenu(FactoryInterface $factory, array $options)
    {
        $menu = $this->createMenu($factory, parent::POSITION_LEFT);

        if ($this->isGranted('ROLE_USER')) {
            foreach ($this->get('app.camera')->getAvailableCameras() as $name) {
                $this->addRoute($menu, $name, 'live', ['name' => $name]);
            }
        }

        return $this->selectActiveMenu($menu);
    }

    public function mainRightMenu(FactoryInterface $factory, array $options)
    {
        $menu = $this->createMenu($factory, parent::POSITION_RIGHT);

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addRoute($menu, 'base.menu.admin.users', 'admin_users');
        }

        return $this->selectActiveMenu($menu);
    }
}
