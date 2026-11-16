<?php

namespace Wexample\SymfonyDocument\Repository;

use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyDocument\Entity\DocumentSegment;
use Wexample\SymfonyDocument\Entity\Traits\Manipulator\DocumentSegmentEntityManipulatorTrait;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyPerson\Entity\Person;

/**
 * @method DocumentSegment|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentSegment      saveNewDocumentSegment(Document $document, int $startOffset, int $endOffset, ?string $speaker = null)
 */
class DocumentSegmentRepository extends AbstractRepository
{
    use DocumentSegmentEntityManipulatorTrait;

    /**
     * Appended after the segments the document already has.
     */
    public function createNewDocumentSegment(
        Document $document,
        int $startOffset,
        int $endOffset,
        ?string $speaker = null
    ): DocumentSegment {
        return new DocumentSegment($document, $startOffset, $endOffset, $speaker);
    }

    /**
     * How much $person has spoken: their interventions, the characters they span,
     * the first and the last day they were heard.
     *
     * @return array{segments: int, characters: int, first: ?string, last: ?string}
     */
    public function getPersonStats(Person $person): array
    {
        $row = $this->createQueryBuilder('segment')
            ->select('COUNT(segment.id) AS segments')
            ->addSelect('COALESCE(SUM(segment.endOffset - segment.startOffset), 0) AS characters')
            ->addSelect('MIN(document.dateDelivered) AS first')
            ->addSelect('MAX(document.dateDelivered) AS last')
            ->innerJoin('segment.document', 'document')
            ->where('segment.person = :person')
            ->setParameter('person', $person->getId(), UuidType::NAME)
            ->getQuery()
            ->getSingleResult();

        return [
            'segments' => (int) $row['segments'],
            'characters' => (int) $row['characters'],
            'first' => $row['first'],
            'last' => $row['last'],
        ];
    }
}
