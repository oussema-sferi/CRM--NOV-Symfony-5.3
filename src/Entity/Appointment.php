<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AppointmentRepository::class)
 */
class Appointment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $start;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $end;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $appointmentNotes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $appointmentCallNotes;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="appointment")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="appointments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDone;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $doneAt;


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

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAppointmentNotes(): ?string
    {
        return $this->appointmentNotes;
    }

    public function setAppointmentNotes(string $appointmentNotes): self
    {
        $this->appointmentNotes = $appointmentNotes;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
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

    public function getIsDone(): ?bool
    {
        return $this->isDone;
    }

    public function setIsDone(?bool $isDone): self
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getDoneAt()
    {
        return $this->doneAt;
    }

    /**
     * @param mixed $doneAt
     */
    public function setDoneAt($doneAt): void
    {
        $this->doneAt = $doneAt;
    }

    /**
     * @return mixed
     */
    public function getAppointmentCallNotes()
    {
        return $this->appointmentCallNotes;
    }

    /**
     * @param mixed $appointmentCallNotes
     */
    public function setAppointmentCallNotes($appointmentCallNotes): void
    {
        $this->appointmentCallNotes = $appointmentCallNotes;
    }



}
