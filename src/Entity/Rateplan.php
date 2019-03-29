<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RateplanRepository")
 */
class Rateplan
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $bookDate;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cxl;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookDate(): ?\DateTimeInterface
    {
        return $this->bookDate;
    }

    public function setBookDate(\DateTimeInterface $bookDate): self
    {
        $this->bookDate = $bookDate;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCxl(): ?int
    {
        return $this->cxl;
    }

    public function setCxl(?int $cxl): self
    {
        $this->cxl = $cxl;

        return $this;
    }
}
