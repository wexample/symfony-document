<?php

namespace Wexample\SymfonyDocument\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Stringable;
use Wexample\SymfonyDocument\Repository\DocumentSegmentRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyPerson\Entity\Person;

/**
 * A passage of a document, between two character offsets of its content, and who
 * speaks it: one turn of an interview, one voice of a debate.
 *
 * It carries no text of its own — the text is the document's, read through the
 * offsets — so that the passage and the document can never disagree.
 */
#[ORM\Entity(repositoryClass: DocumentSegmentRepository::class)]
#[ORM\Table(name: 'document_segment')]
#[ORM\UniqueConstraint(columns: ['document_id', 'position'])]
class DocumentSegment extends AbstractEntity implements Stringable
{
    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'segments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Document $document;

    /**
     * Its rank in the document, from 0.
     */
    #[ORM\Column(type: Types::INTEGER)]
    protected int $position;

    /**
     * Who speaks, as the source names them (`JOURNALISTE`, `Jean Jaurès`); null when
     * the source does not say.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $speaker;

    /**
     * Who the speaker is, when the source says it: `speaker` stays what the text
     * reads (« M. le président »), this is the person behind it.
     */
    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Person $person = null;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $startOffset;

    /**
     * Exclusive: the first character after the passage.
     */
    #[ORM\Column(type: Types::INTEGER)]
    protected int $endOffset;

    /**
     * @throws InvalidArgumentException when the offsets fall outside the content or
     *                                  enclose nothing
     */
    public function __construct(
        Document $document,
        int $startOffset,
        int $endOffset,
        ?string $speaker = null
    ) {
        parent::__construct();

        if ($startOffset < 0 || $endOffset <= $startOffset || $endOffset > $document->getContentLength()) {
            throw new InvalidArgumentException(sprintf('Segment [%d, %d[ does not fit in a content of %d characters.', $startOffset, $endOffset, $document->getContentLength()));
        }

        $this->document = $document;
        $this->position = $document->getSegments()->count();
        $this->startOffset = $startOffset;
        $this->endOffset = $endOffset;
        $this->speaker = $speaker;

        $document->addSegment($this);
    }

    public function __toString(): string
    {
        return $this->getText();
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getSpeaker(): ?string
    {
        return $this->speaker;
    }

    public function setSpeaker(?string $speaker): static
    {
        $this->speaker = $speaker;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }

    public function getStartOffset(): int
    {
        return $this->startOffset;
    }

    public function getEndOffset(): int
    {
        return $this->endOffset;
    }

    public function getText(): string
    {
        return $this->document->getContentSlice($this->startOffset, $this->endOffset);
    }
}
