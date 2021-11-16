<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=QuestionRepository::class)
 */
class Question
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $ask;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $helper;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $answerType;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $answers = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $categoryFactor;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $globalFactor;

    /**
     * @ORM\Column(type="boolean")
     */
    private $required;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $Qlink = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $Qnext = [];

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $hexColor;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUpdate;

    /**
     * @ORM\Column(type="integer")
     */
    private $rang;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="questions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;


    const ANSWERTYPES = [
        // inf. 10 == specials
        1 => ['label' => "Sélection unique",                'type' => "radio",      'method' => null],
        2 => ['label' => "Sélection multiple (+)",          'type' => "checkbox",   'method' => "+" ],
        3 => ['label' => "Sélection multiple (OR)",         'type' => "checkbox",   'method' => "|" ],
        4 => ['label' => "Sélection multiple (AND)",        'type' => "checkbox",   'method' => "&" ],
        5 => ['label' => "Sélection multiple (ALL = 1)",    'type' => "checkbox",   'method' => "*" ],

        // more here
        11 => ['label' => "Champ libre", 'type' => "text", 'method' => null],
        12 => ['label' => "Date (JJ/MM/AAAA)", 'type' => "date", 'method' => "d/m/Y"],
        13 => ['label' => "Date (MM/DD/YYYY)", 'type' => "date", 'method' => "m/d/Y"],
    ];


    public function __construct()
    {
        $this->required   = true;
        $this->createdAt  = new \DateTime();
        $this->lastUpdate = new \DateTime();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAsk(): ?string
    {
        return $this->ask;
    }

    public function setAsk(string $ask): self
    {
        $this->ask = $ask;

        return $this;
    }

    public function getHelper(): ?string
    {
        return $this->helper;
    }

    public function setHelper(string $helper): self
    {
        $this->helper = $helper;

        return $this;
    }

    public function getAnswerType(): int
    {
        return $this->answerType;
    }

    public function setAnswerType(int $answerType): self
    {
        $this->answerType = $answerType;

        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(?array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    public function getCategoryFactor(): ?int
    {
        return $this->categoryFactor;
    }

    public function setCategoryFactor(?int $categoryFactor): self
    {
        $this->categoryFactor = $categoryFactor;

        return $this;
    }

    public function getGlobalFactor(): ?int
    {
        return $this->globalFactor;
    }

    public function setGlobalFactor(?int $globalFactor): self
    {
        $this->globalFactor = $globalFactor;

        return $this;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getQlink(): ?array
    {
        return $this->Qlink;
    }

    public function setQlink(?array $Qlink): self
    {
        $this->Qlink = $Qlink;

        return $this;
    }

    public function getQnext(): ?array
    {
        return $this->Qnext;
    }

    public function setQnext(?array $Qnext): self
    {
        $this->Qnext = $Qnext;

        return $this;
    }

    public function getHexColor(): ?string
    {
        return $this->hexColor;
    }

    public function setHexColor(?string $hexColor): self
    {
        $this->hexColor = $hexColor;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

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

    public function getRang(): ?int
    {
        return $this->rang;
    }

    public function setRang(int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
