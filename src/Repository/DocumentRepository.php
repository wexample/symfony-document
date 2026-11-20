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
     * The documents where $person speaks, the most recent first; of one kind
     * only when $kind says it.
     *
     * @return list<Document>
     */
    public function findByPerson(
        Person $person,
        int $limit = 50,
        int $offset = 0,
        ?DocumentKind $kind = null
    ): array {
        return $this->queryByPerson($person, $kind)
            ->orderBy('document.dateDelivered', 'DESC')
            ->addOrderBy('document.dateCreated', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByPerson(
        Person $person,
        ?DocumentKind $kind = null
    ): int {
        return (int) $this->queryByPerson($person, $kind)
            ->select('COUNT(document.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function queryByPerson(
        Person $person,
        ?DocumentKind $kind
    ): QueryBuilder {
        $query = $this->createQueryBuilder('document')
            ->where(sprintf('EXISTS (SELECT 1 FROM %s segment WHERE segment.document = document AND segment.person = :person)', DocumentSegment::class))
            ->setParameter('person', $person->getId(), UuidType::NAME);

        if (null !== $kind) {
            $query->andWhere('document.kind = :kind')->setParameter('kind', $kind->value);
        }

        return $query;
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
