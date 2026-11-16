<?php

namespace Wexample\SymfonyDocument\Entity\Traits\Manipulator;

use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;

trait DocumentEntityManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return Document::class;
    }
}
