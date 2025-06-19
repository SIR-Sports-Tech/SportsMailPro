<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FileNameLength extends Constraint
{
    public string $message   = 'mautic.core.filename.length';
    public int $maxLength    = 191;

    public function validatedBy()
    {
        return FileNameLengthValidator::class;
    }
}
