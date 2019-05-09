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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Department", inversedBy="users")
     */
    private $department;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=1, nullable=true)
     */
    private $holidays;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getHolidays()
    {
        return $this->holidays;
    }

    public function setHolidays($holidays): self
    {
        $this->holidays = $holidays;

        return $this;
    }

}