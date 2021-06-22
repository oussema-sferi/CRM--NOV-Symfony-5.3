<?php

namespace App\Entity;

use App\Repository\QualifiedCallRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=QualifiedCallRepository::class)
 */
class QualifiedCall
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
    private $statusDetails;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatusDetails(): ?string
    {
        return $this->statusDetails;
    }

    public function setStatusDetails(string $statusDetails): self
    {
        $this->statusDetails = $statusDetails;

        return $this;
    }
}
