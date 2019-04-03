<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BudgetRepository")
 */
class Budget
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $month;

    /**
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $accomodation;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $otherRevenue;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $occupancy;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $rate;

    /**
     * @ORM\Column(type="integer")
     */
    private $roomNights;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getAccomodation()
    {
        return $this->accomodation;
    }

    public function setAccomodation($accomodation): self
    {
        $this->accomodation = $accomodation;

        return $this;
    }

    public function getOtherRevenue()
    {
        return $this->otherRevenue;
    }

    public function setOtherRevenue($otherRevenue): self
    {
        $this->otherRevenue = $otherRevenue;

        return $this;
    }

    public function getOccupancy()
    {
        return $this->occupancy;
    }

    public function setOccupancy($occupancy): self
    {
        $this->occupancy = $occupancy;

        return $this;
    }

    public function getRate()
    {
        return $this->rate;
    }

    public function setRate($rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRoomNights(): ?int
    {
        return $this->roomNights;
    }

    public function setRoomNights(int $roomNights): self
    {
        $this->roomNights = $roomNights;

        return $this;
    }
}
