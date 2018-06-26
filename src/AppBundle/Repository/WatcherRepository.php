<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Watcher;
use BaseBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class WatcherRepository extends EntityRepository
{
    public function save(User $user, $name)
    {
        $entity = $this->findOneBy(['user' => $user, 'camera' => $name]);
        if (is_null($entity)) {
            $entity = new Watcher();
            $entity->setUser($user);
            $entity->setCamera($name);
        }
        $entity->setTm(time());

        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    public function getActivity($camera)
    {
        return $this->findByCamera($camera, ['tm' => 'DESC']);
    }
}
