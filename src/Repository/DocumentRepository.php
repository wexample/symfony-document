<?php

namespace Wexample\SymfonyDocument\Repository;

use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyDocument\Entity\Traits\Manipulator\DocumentEntityManipulatorTrait;
use Wexample\SymfonyDocument\Enum\DocumentKind;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document      saveNewDocument(string $title, DocumentKind $kind, string $content)
 */
class DocumentRepository extends AbstractRepository
{
    use DocumentEntityManipulatorTrait;

    public function createNewDocument(
        string $title,
        DocumentKind $kind,
        string $content
    ): Document {
        return new Document($title, $kind, $content);
    }

    /**
     * The most recently delivered first; those with no date last, by when they were
     * recorded.
     *
     * @return list<Document>
     */
    public function findLatest(int $limit = 50): array
    {
        // DQL has no NULLS LAST, and PostgreSQL puts nulls first in a descending order.
        return $this->createQueryBuilder('document')
            ->addSelect('CASE WHEN document.dateDelivered IS NULL THEN 1 ELSE 0 END AS HIDDEN undated')
            ->orderBy('undated', 'ASC')
            ->addOrderBy('document.dateDelivered', 'DESC')
            ->addOrderBy('document.dateCreated', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
