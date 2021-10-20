<?php

namespace App\Entity;

use App\Repository\GeographicAreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GeographicAreaRepository::class)
 */
class GeographicArea
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $designation;

    /**
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="geographicArea")
     */
    private $clients;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $code;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="geographicAreas")
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity=GeographicZoneEvent::class, mappedBy="geographicAreas")
     */
    private $geographicZoneEvents;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->geographicZoneEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
            $client->setGeographicArea($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getGeographicArea() === $this) {
                $client->setGeographicArea(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function __toString(){
        // to show the name of the Category in the select
        return $this->designation;
        // to show the id of the Category in the select
        // return $this->id;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addGeographicArea($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeGeographicArea($this);
        }

        return $this;
    }

    /**
     * @return Collection|GeographicZoneEvent[]
     */
    public function getGeographicZoneEvents(): Collection
    {
        return $this->geographicZoneEvents;
    }

    public function addGeographicZoneEvent(GeographicZoneEvent $geographicZoneEvent): self
    {
        if (!$this->geographicZoneEvents->contains($geographicZoneEvent)) {
            $this->geographicZoneEvents[] = $geographicZoneEvent;
            $geographicZoneEvent->addGeographicArea($this);
        }

        return $this;
    }

    public function removeGeographicZoneEvent(GeographicZoneEvent $geographicZoneEvent): self
    {
        if ($this->geographicZoneEvents->removeElement($geographicZoneEvent)) {
            $geographicZoneEvent->removeGeographicArea($this);
        }

        return $this;
    }
}
