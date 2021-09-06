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
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
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
     */
    private $password;

    /**
     * @Assert\EqualTo(propertyPath="password", message="Vous n'avez pas entré le même mot de passe")
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
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="teleprospector")
     * @var ArrayCollection<User> $commercials
     */
    private Collection $commercials;

    /**
     * @ORM\ManyToMany(targetEntity=GeographicArea::class, inversedBy="users")
     */
    private $geographicAreas;


    public function __construct()
    {
        $this->calls = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->commercials = new ArrayCollection();
        $this->geographicAreas = new ArrayCollection();
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


}
