<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AccessGroup", mappedBy="user", cascade={"persist", "remove"})
     */
    private $accessGroup;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function getAccessGroup(): ?AccessGroup
    {
        return $this->accessGroup;
    }

    public function setAccessGroup(AccessGroup $accessGroup): self
    {
        $this->accessGroup = $accessGroup;

        // set the owning side of the relation if necessary
        if ($this !== $accessGroup->getUser()) {
            $accessGroup->setUser($this);
        }

        return $this;
    }

}