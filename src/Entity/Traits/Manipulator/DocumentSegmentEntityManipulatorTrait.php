<?php

namespace Wexample\SymfonyDocument\Entity\Traits\Manipulator;

use Wexample\SymfonyDocument\Entity\DocumentSegment;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;

trait DocumentSegmentEntityManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return DocumentSegment::class;
    }
}
