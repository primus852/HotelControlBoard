<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RatetypeRepository")
 */
class Ratetype
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $nameShort;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isBase;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Ratecode", mappedBy="Ratetype", orphanRemoval=true)
     */
    private $ratecodes;

    public function __construct()
    {
        $this->ratecodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNameShort(): ?string
    {
        return $this->nameShort;
    }

    public function setNameShort(string $nameShort): self
    {
        $this->nameShort = $nameShort;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsBase(): ?bool
    {
        return $this->isBase;
    }

    public function setIsBase(bool $isBase): self
    {
        $this->isBase = $isBase;

        return $this;
    }

    /**
     * @return Collection|Ratecode[]
     */
    public function getRatecodes(): Collection
    {
        return $this->ratecodes;
    }

    public function addRatecode(Ratecode $ratecode): self
    {
        if (!$this->ratecodes->contains($ratecode)) {
            $this->ratecodes[] = $ratecode;
            $ratecode->setRatetype($this);
        }

        return $this;
    }

    public function removeRatecode(Ratecode $ratecode): self
    {
        if ($this->ratecodes->contains($ratecode)) {
            $this->ratecodes->removeElement($ratecode);
            // set the owning side to null (unless already changed)
            if ($ratecode->getRatetype() === $this) {
                $ratecode->setRatetype(null);
            }
        }

        return $this;
    }
}
