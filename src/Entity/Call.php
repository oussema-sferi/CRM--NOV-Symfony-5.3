<?php

namespace App\Entity;

use App\Repository\CallRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CallRepository::class)
 * @ORM\Table(name="`call`")
 */
class Call
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $generalStatus;

    /**
     * @ORM\Column(type="integer")
     */
    private $statusDetails;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $callNotes;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="calls")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="calls")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;




    public function getId(): ?int
    {
        return $this->id;
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

    public function getGeneralStatus(): ?int
    {
        return $this->generalStatus;
    }

    public function setGeneralStatus(int $generalStatus): self
    {
        $this->generalStatus = $generalStatus;

        return $this;
    }

    public function getCallNotes(): ?string
    {
        return $this->callNotes;
    }

    public function setCallNotes(?string $callNotes): self
    {
        $this->callNotes = $callNotes;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatusDetails(): ?int
    {
        return $this->statusDetails;
    }

    public function setStatusDetails(int $statusDetails): self
    {
        $this->statusDetails = $statusDetails;

        return $this;
    }


}
