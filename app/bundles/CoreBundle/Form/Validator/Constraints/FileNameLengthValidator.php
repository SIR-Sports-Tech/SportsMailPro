<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a file's original name doesn't exceed the specified maximum length.
 */
class FileNameLengthValidator extends ConstraintValidator
{
    /**
     * @param UploadedFile|mixed $value
     */
    public function validate($value, FileNameLength $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$value instanceof UploadedFile) {
            return;
        }

        $originalFilename = $value->getClientOriginalName();

        if (strlen($originalFilename) > $constraint->maxLength) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%length%', (string) $constraint->maxLength)
                ->addViolation();
        }
    }
}
