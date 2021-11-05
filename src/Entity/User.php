<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Un utilisateur avec cette addresse email existe déjà!")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\Length(min="8", minMessage="Le mot de passe doit contenir au moins 8 caractères!")
     * @Assert\Regex(pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*\W).*$^", message="Le mot de passe doit contenir au moins, un caractère en majuscule, un en minuscule, un numéro et un caractère spécial!")
     */
    private $password;

    /**
     * @Assert\EqualTo(propertyPath="password", message="Les mots de passe doivent être identiques!")
     */
    public $confirmPassword;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\OneToMany(targetEntity=Call::class, mappedBy="user")
     */
    private $calls;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="user")
     */
    private $appointments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="commercials")
     * @var User $teleprospector
     */
    private ?User $teleprospector=null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="deletedUsers")
     * @var User $whoDeletedIt
     */
    private ?User $whoDeletedIt=null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="teleprospector")
     * @var ArrayCollection<User> $commercials
     */
    private Collection $commercials;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="whoDeletedIt")
     * @var ArrayCollection<User> $deletedUsers
     */
    private Collection $deletedUsers;

    /**
     * @ORM\ManyToMany(targetEntity=GeographicArea::class, inversedBy="users")
     */
    private $geographicAreas;

    /**
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="creatorUser")
     */
    private $clients;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity=GeographicZoneEvent::class, mappedBy="calendarUser")
     */
    private $geographicZoneEvents;

    /**
     * @ORM\ManyToMany(targetEntity=Client::class, mappedBy="callersUsers")
     */
    private $calledClients;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="appointmentFixer")
     */
    private $fixedAppointments;

    /**
     * @ORM\ManyToMany(targetEntity=Client::class, inversedBy="processingUsers")
     */
    private $processedClients;

    /**
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="whoDeletedIt")
     */
    private $deletedClients;

    /**
     * @ORM\OneToMany(targetEntity=Call::class, mappedBy="whoDeletedIt")
     */
    private $deletedCalls;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="whoDeletedIt")
     */
    private $deletedAppointments;

    /**
     * @ORM\OneToMany(targetEntity=Process::class, mappedBy="processorUser")
     */
    private $processes;


    public function __construct()
    {
        $this->calls = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->commercials = new ArrayCollection();
        $this->deletedUsers = new ArrayCollection();
        $this->geographicAreas = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->geographicZoneEvents = new ArrayCollection();
        $this->calledClients = new ArrayCollection();
        $this->fixedAppointments = new ArrayCollection();
        $this->processedClients = new ArrayCollection();
        $this->deletedClients = new ArrayCollection();
        $this->deletedCalls = new ArrayCollection();
        $this->deletedAppointments = new ArrayCollection();
        $this->processes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection|Call[]
     */
    public function getCalls(): Collection
    {
        return $this->calls;
    }

    public function addCall(Call $call): self
    {
        if (!$this->calls->contains($call)) {
            $this->calls[] = $call;
            $call->setUser($this);
        }

        return $this;
    }

    public function removeCall(Call $call): self
    {
        if ($this->calls->removeElement($call)) {
            // set the owning side to null (unless already changed)
            if ($call->getUser() === $this) {
                $call->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(){
        // to show the name of the Category in the select
        return $this->firstName;
        // to show the id of the Category in the select
        // return $this->id;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return User
     */
    public function getTeleprospector(): ?User
    {
        return $this->teleprospector;
    }


    /**
     * @param User $teleprospector
     */
    public function setTeleprospector(?User $teleprospector): void
    {
        $this->teleprospector = $teleprospector;
    }

    /**
     * @return User
     */
    public function getWhoDeletedIt(): ?User
    {
        return $this->whoDeletedIt;
    }

    /**
     * @param User $whoDeletedIt
     */
    public function setWhoDeletedIt(?User $whoDeletedIt): void
    {
        $this->whoDeletedIt = $whoDeletedIt;
    }

    /**
     * @return ArrayCollection
     */
    public function getCommercials()
    {
        return $this->commercials;
    }

    /**
     * @param ArrayCollection $commercials
     */
    public function setCommercials($commercials): void
    {
        $this->commercials = $commercials;
    }

    public function removeCommercial(User $commercial): self
    {
        $this->commercials->removeElement($commercial);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getDeletedUsers()
    {
        return $this->deletedUsers;
    }

    /**
     * @param ArrayCollection $deletedUsers
     */
    public function setDeletedUsers($deletedUsers): void
    {
        $this->deletedUsers = $deletedUsers;
    }

    public function removeDeletedUsers(User $deletedUser): self
    {
        $this->deletedUsers->removeElement($deletedUser);

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
            $client->setCreatorUser($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getCreatorUser() === $this) {
                $client->setCreatorUser(null);
            }
        }

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletionDate(): ?\DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(?\DateTimeInterface $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

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
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
            $geographicZoneEvent->setCalendarUser($this);
        }

        return $this;
    }

    public function removeGeographicZoneEvent(GeographicZoneEvent $geographicZoneEvent): self
    {
        if ($this->geographicZoneEvents->removeElement($geographicZoneEvent)) {
            // set the owning side to null (unless already changed)
            if ($geographicZoneEvent->getCalendarUser() === $this) {
                $geographicZoneEvent->setCalendarUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getCalledClients(): Collection
    {
        return $this->calledClients;
    }

    public function addCalledClient(Client $calledClient): self
    {
        if (!$this->calledClients->contains($calledClient)) {
            $this->calledClients[] = $calledClient;
            $calledClient->addCallersUser($this);
        }

        return $this;
    }

    public function removeCalledClient(Client $calledClient): self
    {
        if ($this->calledClients->removeElement($calledClient)) {
            $calledClient->removeCallersUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getFixedAppointments(): Collection
    {
        return $this->fixedAppointments;
    }

    public function addFixedAppointment(Appointment $fixedAppointment): self
    {
        if (!$this->fixedAppointments->contains($fixedAppointment)) {
            $this->fixedAppointments[] = $fixedAppointment;
            $fixedAppointment->setAppointmentFixer($this);
        }

        return $this;
    }

    public function removeFixedAppointment(Appointment $fixedAppointment): self
    {
        if ($this->fixedAppointments->removeElement($fixedAppointment)) {
            // set the owning side to null (unless already changed)
            if ($fixedAppointment->getAppointmentFixer() === $this) {
                $fixedAppointment->setAppointmentFixer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getProcessedClients(): Collection
    {
        return $this->processedClients;
    }

    public function addProcessedClient(Client $processedClient): self
    {
        if (!$this->processedClients->contains($processedClient)) {
            $this->processedClients[] = $processedClient;
        }

        return $this;
    }

    public function removeProcessedClient(Client $processedClient): self
    {
        $this->processedClients->removeElement($processedClient);

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getDeletedClients(): Collection
    {
        return $this->deletedClients;
    }

    public function addDeletedClient(Client $deletedClient): self
    {
        if (!$this->deletedClients->contains($deletedClient)) {
            $this->deletedClients[] = $deletedClient;
            $deletedClient->setWhoDeletedIt($this);
        }

        return $this;
    }

    public function removeDeletedClient(Client $deletedClient): self
    {
        if ($this->deletedClients->removeElement($deletedClient)) {
            // set the owning side to null (unless already changed)
            if ($deletedClient->getWhoDeletedIt() === $this) {
                $deletedClient->setWhoDeletedIt(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Call[]
     */
    public function getDeletedCalls(): Collection
    {
        return $this->deletedCalls;
    }

    public function addDeletedCall(Call $deletedCall): self
    {
        if (!$this->deletedCalls->contains($deletedCall)) {
            $this->deletedCalls[] = $deletedCall;
            $deletedCall->setWhoDeletedIt($this);
        }

        return $this;
    }

    public function removeDeletedCall(Call $deletedCall): self
    {
        if ($this->deletedCalls->removeElement($deletedCall)) {
            // set the owning side to null (unless already changed)
            if ($deletedCall->getWhoDeletedIt() === $this) {
                $deletedCall->setWhoDeletedIt(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getDeletedAppointments(): Collection
    {
        return $this->deletedAppointments;
    }

    public function addDeletedAppointment(Appointment $deletedAppointment): self
    {
        if (!$this->deletedAppointments->contains($deletedAppointment)) {
            $this->deletedAppointments[] = $deletedAppointment;
            $deletedAppointment->setWhoDeletedIt($this);
        }

        return $this;
    }

    public function removeDeletedAppointment(Appointment $deletedAppointment): self
    {
        if ($this->deletedAppointments->removeElement($deletedAppointment)) {
            // set the owning side to null (unless already changed)
            if ($deletedAppointment->getWhoDeletedIt() === $this) {
                $deletedAppointment->setWhoDeletedIt(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Process[]
     */
    public function getProcesses(): Collection
    {
        return $this->processes;
    }

    public function addProcess(Process $process): self
    {
        if (!$this->processes->contains($process)) {
            $this->processes[] = $process;
            $process->setProcessorUser($this);
        }

        return $this;
    }

    public function removeProcess(Process $process): self
    {
        if ($this->processes->removeElement($process)) {
            // set the owning side to null (unless already changed)
            if ($process->getProcessorUser() === $this) {
                $process->setProcessorUser(null);
            }
        }

        return $this;
    }

}