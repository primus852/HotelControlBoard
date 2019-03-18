<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\JournalBudgetRepository")
 */
class JournalBudget
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
     * @ORM\Column(type="string", length=255)
     */
    private $transNo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $transDesc;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $transTotal;

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

    public function getTransNo(): ?string
    {
        return $this->transNo;
    }

    public function setTransNo(string $transNo): self
    {
        $this->transNo = $transNo;

        return $this;
    }

    public function getTransDesc(): ?string
    {
        return $this->transDesc;
    }

    public function setTransDesc(string $transDesc): self
    {
        $this->transDesc = $transDesc;

        return $this;
    }

    public function getTransTotal()
    {
        return $this->transTotal;
    }

    public function setTransTotal($transTotal): self
    {
        $this->transTotal = $transTotal;

        return $this;
    }
}
