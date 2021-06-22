<?php

namespace App\Entity;

use App\Repository\NoQualifiedCallRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NoQualifiedCallRepository::class)
 */
class NoQualifiedCall
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
    private $stausDetails;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStausDetails(): ?string
    {
        return $this->stausDetails;
    }

    public function setStausDetails(string $stausDetails): self
    {
        $this->stausDetails = $stausDetails;

        return $this;
    }
}
