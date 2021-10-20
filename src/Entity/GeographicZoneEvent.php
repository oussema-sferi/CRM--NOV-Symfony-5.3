<?php

namespace App\Entity;

use App\Repository\GeographicZoneEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GeographicZoneEventRepository::class)
 */
class GeographicZoneEvent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $start;

    /**
     * @ORM\Column(type="date")
     */
    private $end;

    /**
     * @ORM\ManyToMany(targetEntity=GeographicArea::class, inversedBy="geographicZoneEvents")
     */
    private $geographicAreas;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="geographicZoneEvents")
     * @ORM\JoinColumn(nullable=false)
     */
    private $calendarUser;

    public function __construct()
    {
        $this->geographicAreas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return Collection|GeographicArea[]
     */
    public function getGeographicAreas(): Collection
    {
        return $this->geographicAreas;
    }

    public function addGeographicArea(GeographicArea $geographicArea): self
    {
        if (!$this->geographicAreas->contains($geographicArea)) {
            $this->geographicAreas[] = $geographicArea;
        }

        return $this;
    }

    public function removeGeographicArea(GeographicArea $geographicArea): self
    {
        $this->geographicAreas->removeElement($geographicArea);

        return $this;
    }

    public function getCalendarUser(): ?User
    {
        return $this->calendarUser;
    }

    public function setCalendarUser(?User $calendarUser): self
    {
        $this->calendarUser = $calendarUser;

        return $this;
    }
}
