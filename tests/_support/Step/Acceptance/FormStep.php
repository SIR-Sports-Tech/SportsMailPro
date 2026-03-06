<?php

namespace Step\Acceptance;

use Page\Acceptance\FormPage;

class FormStep extends \AcceptanceTester
{
    public function addFormMetaData(): void
    {
        $I = $this;
        // Fill Basic form info
        $I->fillField('mauticform[name]', FormPage::$FORM_NAME);
        $I->fillField('mauticform[postActionProperty]', FormPage::$FORM_POST_ACTION_PROPERTY);
    }

    public function createFormField(
        string $fieldType,
        string $modalHeader,
        string $label,
        string $labelSelector = FormPage::$FORM_FIELD_LABEL_SELECTOR,
        string $saveButtonSelector = FormPage::$FORM_FIELD_SAVE_BUTTON_SELECTOR,
    ): void {
        $I = $this;
        $I->click(FormPage::$ADD_NEW_FIELD_BUTTON_TEXT);
        $I->click($fieldType);
        $I->waitForText($modalHeader, 5);
        $I->waitForElementVisible($labelSelector, 10);
        $I->fillField($labelSelector, $label);
        $I->waitForElementClickable($saveButtonSelector, 10);
        $I->click($saveButtonSelector);
        $I->wait(1);
    }
}
