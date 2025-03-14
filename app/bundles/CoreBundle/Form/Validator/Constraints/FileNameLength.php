<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FileNameLength extends Constraint
{
    public $message   = 'mautic.core.filename.length';
    public $maxLength = 191;

    public function validatedBy()
    {
        return FileNameLengthValidator::class;
    }
}
