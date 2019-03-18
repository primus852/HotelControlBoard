<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HistoryForecastRepository")
 */
class HistoryForecast
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
     * @ORM\Column(type="integer")
     */
    private $bookedRooms;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalRooms;

    /**
     * @ORM\Column(type="integer")
     */
    private $pax;

    /**
     * @ORM\Column(type="integer")
     */
    private $arrivalRooms;

    /**
     * @ORM\Column(type="integer")
     */
    private $departureRooms;

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

    public function getBookedRooms(): ?int
    {
        return $this->bookedRooms;
    }

    public function setBookedRooms(int $bookedRooms): self
    {
        $this->bookedRooms = $bookedRooms;

        return $this;
    }

    public function getTotalRooms(): ?int
    {
        return $this->totalRooms;
    }

    public function setTotalRooms(int $totalRooms): self
    {
        $this->totalRooms = $totalRooms;

        return $this;
    }

    public function getPax(): ?int
    {
        return $this->pax;
    }

    public function setPax(int $pax): self
    {
        $this->pax = $pax;

        return $this;
    }

    public function getArrivalRooms(): ?int
    {
        return $this->arrivalRooms;
    }

    public function setArrivalRooms(int $arrivalRooms): self
    {
        $this->arrivalRooms = $arrivalRooms;

        return $this;
    }

    public function getDepartureRooms(): ?int
    {
        return $this->departureRooms;
    }

    public function setDepartureRooms(int $departureRooms): self
    {
        $this->departureRooms = $departureRooms;

        return $this;
    }
}
