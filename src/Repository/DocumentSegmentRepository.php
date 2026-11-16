<?php

namespace Wexample\SymfonyDocument\Repository;

use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyDocument\Entity\DocumentSegment;
use Wexample\SymfonyDocument\Entity\Traits\Manipulator\DocumentSegmentEntityManipulatorTrait;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

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
}
