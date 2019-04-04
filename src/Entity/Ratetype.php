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

    /**
     * @ORM\Column(type="integer")
     */
    private $maxAdvance;

    /**
     * @ORM\Column(type="boolean")
     */
    private $fairsAllowed;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowMon;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowTue;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowWed;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowThu;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowFri;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowSat;

    /**
     * @ORM\Column(type="boolean")
     */
    private $allowSun;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $fixedSingle;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    private $fixedDouble;

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

    public function getMaxAdvance(): ?int
    {
        return $this->maxAdvance;
    }

    public function setMaxAdvance(int $maxAdvance): self
    {
        $this->maxAdvance = $maxAdvance;

        return $this;
    }

    public function getFairsAllowed(): ?bool
    {
        return $this->fairsAllowed;
    }

    public function setFairsAllowed(bool $fairsAllowed): self
    {
        $this->fairsAllowed = $fairsAllowed;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getAllowMon(): ?bool
    {
        return $this->allowMon;
    }

    public function setAllowMon(bool $allowMon): self
    {
        $this->allowMon = $allowMon;

        return $this;
    }

    public function getAllowTue(): ?bool
    {
        return $this->allowTue;
    }

    public function setAllowTue(bool $allowTue): self
    {
        $this->allowTue = $allowTue;

        return $this;
    }

    public function getAllowWed(): ?bool
    {
        return $this->allowWed;
    }

    public function setAllowWed(bool $allowWed): self
    {
        $this->allowWed = $allowWed;

        return $this;
    }

    public function getAllowThu(): ?bool
    {
        return $this->allowThu;
    }

    public function setAllowThu(bool $allowThu): self
    {
        $this->allowThu = $allowThu;

        return $this;
    }

    public function getAllowFri(): ?bool
    {
        return $this->allowFri;
    }

    public function setAllowFri(bool $allowFri): self
    {
        $this->allowFri = $allowFri;

        return $this;
    }

    public function getAllowSat(): ?bool
    {
        return $this->allowSat;
    }

    public function setAllowSat(bool $allowSat): self
    {
        $this->allowSat = $allowSat;

        return $this;
    }

    public function getAllowSun(): ?bool
    {
        return $this->allowSun;
    }

    public function setAllowSun(bool $allowSun): self
    {
        $this->allowSun = $allowSun;

        return $this;
    }

    public function getFixedSingle()
    {
        return $this->fixedSingle;
    }

    public function setFixedSingle($fixedSingle): self
    {
        $this->fixedSingle = $fixedSingle;

        return $this;
    }

    public function getFixedDouble()
    {
        return $this->fixedDouble;
    }

    public function setFixedDouble($fixedDouble): self
    {
        $this->fixedDouble = $fixedDouble;

        return $this;
    }

    
}
