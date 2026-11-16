<?php

namespace Wexample\SymfonyDocument\Repository;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyDocument\Entity\DocumentSegment;
use Wexample\SymfonyDocument\Entity\Traits\Manipulator\DocumentEntityManipulatorTrait;
use Wexample\SymfonyDocument\Enum\DocumentKind;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyPerson\Entity\Person;

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
     * The document an import already brought from that source, if any.
     */
    public function findOneBySourceKey(string $sourceKey): ?Document
    {
        return $this->findOneBy(['sourceKey' => $sourceKey]);
    }

    /**
     * The documents where $person speaks, the most recent first.
     *
     * @return list<Document>
     */
    public function findByPerson(
        Person $person,
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->queryByPerson($person)
            ->orderBy('document.dateDelivered', 'DESC')
            ->addOrderBy('document.dateCreated', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByPerson(Person $person): int
    {
        return (int) $this->queryByPerson($person)
            ->select('COUNT(document.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function queryByPerson(Person $person): QueryBuilder
    {
        return $this->createQueryBuilder('document')
            ->where(sprintf('EXISTS (SELECT 1 FROM %s segment WHERE segment.document = document AND segment.person = :person)', DocumentSegment::class))
            ->setParameter('person', $person->getId(), UuidType::NAME);
    }

    /**
     * The most recently delivered first; those with no date last, by when they were
     * recorded.
     *
     * @return list<Document>
     */
    public function findLatest(
        int $limit = 50,
        int $offset = 0
    ): array {
        // DQL has no NULLS LAST, and PostgreSQL puts nulls first in a descending order.
        return $this->createQueryBuilder('document')
            ->addSelect('CASE WHEN document.dateDelivered IS NULL THEN 1 ELSE 0 END AS HIDDEN undated')
            ->orderBy('undated', 'ASC')
            ->addOrderBy('document.dateDelivered', 'DESC')
            ->addOrderBy('document.dateCreated', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
