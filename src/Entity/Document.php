<?php

namespace Wexample\SymfonyDocument\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Wexample\SymfonyDocument\Enum\DocumentKind;
use Wexample\SymfonyDocument\Repository\DocumentRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Traits\HasDateCreatedTrait;
use Wexample\SymfonyHelpers\Entity\Traits\HasTitleTrait;

/**
 * A text kept for itself: a speech, an article, a transcribed interview.
 *
 * Its content is fixed once the document exists. What an application builds on the
 * text — a segment, an annotation — points into it by character offsets, and a text
 * changed under them would make every one of them point at the wrong words. A
 * corrected text is a new document.
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
#[ORM\Index(columns: ['date_delivered'])]
class Document extends AbstractEntity implements Stringable
{
    use HasDateCreatedTrait;
    use HasTitleTrait;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: DocumentKind::class)]
    protected DocumentKind $kind;

    #[ORM\Column(type: Types::TEXT)]
    protected string $content;

    /**
     * An IETF tag (`fr`, `en-GB`); null while nobody said.
     */
    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    protected ?string $language = null;

    /**
     * When it was delivered or published — not when it was recorded here.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $dateDelivered = null;

    /**
     * Where the text comes from, as a reader would cite it: `vie-publique.fr`, a newspaper.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $sourceLabel = null;

    #[ORM\Column(type: Types::STRING, length: 2048, nullable: true)]
    protected ?string $sourceUrl = null;

    /**
     * What the document is called where it was imported from, as `<provider>:<id>`
     * (`vie-publique:276543`, `wikisource:Discours_sur_la_misère`): an import finds by
     * it what it already brought, and does not bring it twice. Null for a document
     * written here.
     */
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: true)]
    protected ?string $sourceKey = null;

    /**
     * @var Collection<int, DocumentSegment>
     */
    #[ORM\OneToMany(mappedBy: 'document', targetEntity: DocumentSegment::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $segments;

    public function __construct(
        string $title,
        DocumentKind $kind,
        string $content
    ) {
        parent::__construct();

        $this->setTitle($title);
        $this->kind = $kind;
        $this->content = $content;
        $this->segments = new ArrayCollection();
        $this->setDateCreatedNow();
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getKind(): DocumentKind
    {
        return $this->kind;
    }

    public function setKind(DocumentKind $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * In characters, the unit every offset into the content is counted in.
     */
    public function getContentLength(): int
    {
        return mb_strlen($this->content);
    }

    /**
     * The words between two offsets, counted in characters.
     */
    public function getContentSlice(
        int $startOffset,
        int $endOffset
    ): string {
        return mb_substr($this->content, $startOffset, $endOffset - $startOffset);
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getDateDelivered(): ?DateTimeImmutable
    {
        return $this->dateDelivered;
    }

    public function setDateDelivered(?DateTimeImmutable $dateDelivered): static
    {
        $this->dateDelivered = $dateDelivered;

        return $this;
    }

    public function getSourceLabel(): ?string
    {
        return $this->sourceLabel;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSource(
        ?string $label,
        ?string $url = null
    ): static {
        $this->sourceLabel = $label;
        $this->sourceUrl = $url;

        return $this;
    }

    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    public function setSourceKey(?string $sourceKey): static
    {
        $this->sourceKey = $sourceKey;

        return $this;
    }

    /**
     * @return Collection<int, DocumentSegment>
     */
    public function getSegments(): Collection
    {
        return $this->segments;
    }

    /**
     * The voices heard in the document, in the order they first speak.
     *
     * @return list<string>
     */
    public function getSpeakers(): array
    {
        $speakers = [];

        foreach ($this->segments as $segment) {
            $speaker = $segment->getSpeaker();

            if (null !== $speaker && ! in_array($speaker, $speakers, true)) {
                $speakers[] = $speaker;
            }
        }

        return $speakers;
    }

    /**
     * Kept in step by DocumentSegment's constructor, the owning side.
     *
     * @internal
     */
    public function addSegment(DocumentSegment $segment): void
    {
        $this->segments->add($segment);
    }
}
