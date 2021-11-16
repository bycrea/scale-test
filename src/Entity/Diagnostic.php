<?php

namespace App\Entity;

use App\Repository\DiagnosticRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DiagnosticRepository::class)
 */
class Diagnostic
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
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="array")
     */
    private $questions = [];

    /**
     * @ORM\Column(type="array")
     */
    private $categoriesScales = [];

    /**
     * @ORM\Column(type="array")
     */
    private $globalScale = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable="true")
     */
    private $lastUpdate;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="diagnostic", orphanRemoval=true)
     */
    private $participations;


    public function __construct()
    {
        $this->createdAt      = new \DateTime();
        $this->lastUpdate     = new \DateTime();
        $this->participations = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getQuestions(): ?array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): self
    {
        $this->questions = $questions;

        return $this;
    }

    public function getCategoriesScales(): ?array
    {
        return $this->categoriesScales;
    }

    public function setCategoriesScales(array $categoriesScales): self
    {
        $this->categoriesScales = $categoriesScales;

        return $this;
    }

    public function getGlobalScale(): ?array
    {
        return $this->globalScale;
    }

    public function setGlobalScale(array $globalScale): self
    {
        $this->globalScale = $globalScale;

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

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setDiagnostic($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getDiagnostic() === $this) {
                $participation->setDiagnostic(null);
            }
        }

        return $this;
    }
}
