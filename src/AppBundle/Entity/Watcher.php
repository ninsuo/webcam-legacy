<?php

namespace AppBundle\Entity;

use BaseBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * User.
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WatcherRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Watcher
{
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="BaseBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     * @ORM\Id
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="camera", type="string", length=16)
     * @ORM\Id
     */
    protected $camera;

    /**
     * @var int
     *
     * @ORM\Column(name="tm", type="bigint")
     */
    protected $tm;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Watcher
     */
    public function setId(int $id): Watcher
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Watcher
     */
    public function setUser(User $user): Watcher
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getCamera(): string
    {
        return $this->camera;
    }

    /**
     * @param string $camera
     *
     * @return Watcher
     */
    public function setCamera(string $camera): Watcher
    {
        $this->camera = $camera;

        return $this;
    }

    /**
     * @return int
     */
    public function getTm(): int
    {
        return $this->tm;
    }

    /**
     * @param int $tm
     *
     * @return Watcher
     */
    public function setTm(int $tm): Watcher
    {
        $this->tm = $tm;

        return $this;
    }
}
