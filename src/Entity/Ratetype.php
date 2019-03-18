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
     * @ORM\Column(type="float", nullable=true)
     */
    private $discountAmount;

    /**
     * @ORM\Column(type="boolean")
     */
    private $discountPercent;

    /**
     * @ORM\Column(type="integer")
     */
    private $minStay;

    /**
     * @ORM\Column(type="integer")
     */
    private $daysAdvance;

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

    public function getDiscountAmount(): ?float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?float $discountAmount): self
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getDiscountPercent(): ?bool
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(bool $discountPercent): self
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    public function getMinStay(): ?int
    {
        return $this->minStay;
    }

    public function setMinStay(int $minStay): self
    {
        $this->minStay = $minStay;

        return $this;
    }

    public function getDaysAdvance(): ?int
    {
        return $this->daysAdvance;
    }

    public function setDaysAdvance(int $daysAdvance): self
    {
        $this->daysAdvance = $daysAdvance;

        return $this;
    }

    
}
