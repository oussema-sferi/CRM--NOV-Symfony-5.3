<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 */
class Client
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $companyName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $postalCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mobileNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isUnderContract;


    /**
     * @ORM\OneToMany(targetEntity=Call::class, mappedBy="client")
     */
    private $calls;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="client")
     */
    private $appointments;


    /**
     * @ORM\ManyToOne(targetEntity=Equipment::class, inversedBy="clients")
     */
    private $providedEquipment;

    /**
     * @ORM\ManyToOne(targetEntity=GeographicArea::class, inversedBy="clients")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(2)
     */
    private $geographicArea;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $statusDetail;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="clients")
     */
    private $creatorUser;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="deletedClients")
     */
    private $whoDeletedIt;

    /**
     * @ORM\OneToMany(targetEntity=Process::class, mappedBy="client")
     */
    private $processes;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="processedClients")
     */
    private $processingUsers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="calledClients")
     */
    private $callersUsers;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isProcessed;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="client")
     */
    private $projects;

    /**
     * @ORM\ManyToOne(targetEntity=ClientCategory::class, inversedBy="clients")
     */
    private $clientCategory;

    /**
     * @ORM\OneToMany(targetEntity=PaymentSchedule::class, mappedBy="client")
     */
    private $paymentSchedules;

    /**
     * @ORM\OneToMany(targetEntity=FollowUpCall::class, mappedBy="client")
     */
    private $followUpCalls;


    public function __construct()
    {
        $this->calls = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->callersUsers = new ArrayCollection();
        $this->processingUsers = new ArrayCollection();
        $this->processes = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->paymentSchedules = new ArrayCollection();
        $this->followUpCalls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }



    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
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

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address): void
    {
        $this->address = $address;
    }



    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param mixed $phoneNumber
     */
    public function setPhoneNumber($phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * @param mixed $mobileNumber
     */
    public function setMobileNumber($mobileNumber): void
    {
        $this->mobileNumber = $mobileNumber;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getIsUnderContract()
    {
        return $this->isUnderContract;
    }

    /**
     * @param mixed $isUnderContract
     */
    public function setIsUnderContract($isUnderContract): void
    {
        $this->isUnderContract = $isUnderContract;
    }






    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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
            $call->setClient($this);
        }

        return $this;
    }

    public function removeCall(Call $call): self
    {
        if ($this->calls->removeElement($call)) {
            // set the owning side to null (unless already changed)
            if ($call->getClient() === $this) {
                $call->setClient(null);
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



    public function getProvidedEquipment(): ?Equipment
    {
        return $this->providedEquipment;
    }

    public function setProvidedEquipment(?Equipment $providedEquipment): self
    {
        $this->providedEquipment = $providedEquipment;

        return $this;
    }

    public function getGeographicArea(): ?GeographicArea
    {
        return $this->geographicArea;
    }

    public function setGeographicArea(?GeographicArea $geographicArea): self
    {
        $this->geographicArea = $geographicArea;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStatusDetail(): ?int
    {
        return $this->statusDetail;
    }

    public function setStatusDetail(?int $statusDetail): self
    {
        $this->statusDetail = $statusDetail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAppointments()
    {
        return $this->appointments;
    }

    /**
     * @param mixed $appointments
     */
    public function setAppointments($appointments): void
    {
        $this->appointments = $appointments;
    }

    public function getCreatorUser(): ?User
    {
        return $this->creatorUser;
    }

    public function setCreatorUser(?User $creatorUser): self
    {
        $this->creatorUser = $creatorUser;

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
     * @return Collection|User[]
     */
    public function getCallersUsers(): Collection
    {
        return $this->callersUsers;
    }

    public function addCallersUser(User $callersUser): self
    {
        if (!$this->callersUsers->contains($callersUser)) {
            $this->callersUsers[] = $callersUser;
        }

        return $this;
    }

    public function removeCallersUser(User $callersUser): self
    {
        $this->callersUsers->removeElement($callersUser);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getProcessingUsers(): Collection
    {
        return $this->processingUsers;
    }

    public function addProcessingUser(User $processingUser): self
    {
        if (!$this->processingUsers->contains($processingUser)) {
            $this->processingUsers[] = $processingUser;
            $processingUser->addProcessedClient($this);
        }

        return $this;
    }

    public function removeProcessingUser(User $processingUser): self
    {
        if ($this->processingUsers->removeElement($processingUser)) {
            $processingUser->removeProcessedClient($this);
        }

        return $this;
    }

    public function getWhoDeletedIt(): ?User
    {
        return $this->whoDeletedIt;
    }

    public function setWhoDeletedIt(?User $whoDeletedIt): self
    {
        $this->whoDeletedIt = $whoDeletedIt;

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
            $process->setClient($this);
        }

        return $this;
    }

    public function removeProcess(Process $process): self
    {
        if ($this->processes->removeElement($process)) {
            // set the owning side to null (unless already changed)
            if ($process->getClient() === $this) {
                $process->setClient(null);
            }
        }

        return $this;
    }

    public function getIsProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
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
            $project->setClient($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getClient() === $this) {
                $project->setClient(null);
            }
        }

        return $this;
    }

    public function getClientCategory(): ?ClientCategory
    {
        return $this->clientCategory;
    }

    public function setClientCategory(?ClientCategory $clientCategory): self
    {
        $this->clientCategory = $clientCategory;

        return $this;
    }

    /**
     * @return Collection|PaymentSchedule[]
     */
    public function getPaymentSchedules(): Collection
    {
        return $this->paymentSchedules;
    }

    public function addPaymentSchedule(PaymentSchedule $paymentSchedule): self
    {
        if (!$this->paymentSchedules->contains($paymentSchedule)) {
            $this->paymentSchedules[] = $paymentSchedule;
            $paymentSchedule->setClient($this);
        }

        return $this;
    }

    public function removePaymentSchedule(PaymentSchedule $paymentSchedule): self
    {
        if ($this->paymentSchedules->removeElement($paymentSchedule)) {
            // set the owning side to null (unless already changed)
            if ($paymentSchedule->getClient() === $this) {
                $paymentSchedule->setClient(null);
            }
        }

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
            $followUpCall->setClient($this);
        }

        return $this;
    }

    public function removeFollowUpCall(FollowUpCall $followUpCall): self
    {
        if ($this->followUpCalls->removeElement($followUpCall)) {
            // set the owning side to null (unless already changed)
            if ($followUpCall->getClient() === $this) {
                $followUpCall->setClient(null);
            }
        }

        return $this;
    }

}