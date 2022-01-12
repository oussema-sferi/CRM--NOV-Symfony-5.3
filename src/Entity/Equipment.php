<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EquipmentRepository::class)
 */
class Equipment
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
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="providedEquipment")
     */
    private $clients;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="equipment")
     */
    private $projects;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity=FollowUpCall::class, mappedBy="associatedEquipment")
     */
    private $followUpCalls;

   

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->followUpCalls = new ArrayCollection();
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
            $client->setProvidedEquipment($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getProvidedEquipment() === $this) {
                $client->setProvidedEquipment(null);
            }
        }

        return $this;
    }

    public function __toString(){
        // to show the name of the Category in the select
        return $this->designation;
        // to show the id of the Category in the select
        // return $this->id;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setEquipment($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getEquipment() === $this) {
                $project->setEquipment(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|FollowUpCall[]
     */
    public function getFollowUpCalls(): Collection
    {
        return $this->followUpCalls;
    }

    public function addFollowUpCall(FollowUpCall $followUpCall): self
    {
        if (!$this->followUpCalls->contains($followUpCall)) {
            $this->followUpCalls[] = $followUpCall;
            $followUpCall->setAssociatedEquipment($this);
        }

        return $this;
    }

    public function removeFollowUpCall(FollowUpCall $followUpCall): self
    {
        if ($this->followUpCalls->removeElement($followUpCall)) {
            // set the owning side to null (unless already changed)
            if ($followUpCall->getAssociatedEquipment() === $this) {
                $followUpCall->setAssociatedEquipment(null);
            }
        }

        return $this;
    }

}
