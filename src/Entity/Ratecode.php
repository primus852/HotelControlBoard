<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RatecodeRepository")
 */
class Ratecode
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Ratetype", inversedBy="ratecodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Ratetype;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $priceSingle;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $priceDouble;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $priceTriple;

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

    public function getRatetype(): ?Ratetype
    {
        return $this->Ratetype;
    }

    public function setRatetype(?Ratetype $Ratetype): self
    {
        $this->Ratetype = $Ratetype;

        return $this;
    }

    public function getPriceSingle()
    {
        return $this->priceSingle;
    }

    public function setPriceSingle($priceSingle): self
    {
        $this->priceSingle = $priceSingle;

        return $this;
    }

    public function getPriceDouble()
    {
        return $this->priceDouble;
    }

    public function setPriceDouble($priceDouble): self
    {
        $this->priceDouble = $priceDouble;

        return $this;
    }

    public function getPriceTriple()
    {
        return $this->priceTriple;
    }

    public function setPriceTriple($priceTriple): self
    {
        $this->priceTriple = $priceTriple;

        return $this;
    }
}
