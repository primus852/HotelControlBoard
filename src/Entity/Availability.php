<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AvailabilityRepository")
 */
class Availability
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Roomtype", inversedBy="availabilities")
     * @ORM\JoinColumn(nullable=false)
     */
    private $roomType;

    /**
     * @ORM\Column(type="integer")
     */
    private $available;

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

    public function getRoomType(): ?Roomtype
    {
        return $this->roomType;
    }

    public function setRoomType(?Roomtype $roomType): self
    {
        $this->roomType = $roomType;

        return $this;
    }

    public function getAvailable(): ?int
    {
        return $this->available;
    }

    public function setAvailable(int $available): self
    {
        $this->available = $available;

        return $this;
    }
}
