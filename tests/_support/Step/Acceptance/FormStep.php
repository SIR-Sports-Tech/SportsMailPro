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
        ?string $labelSelector = null,
        ?string $saveButtonSelector = null,
    ): void {
        $I = $this;
        $labelSelector ??= FormPage::$FORM_FIELD_LABEL_SELECTOR;
        $saveButtonSelector ??= FormPage::$FORM_FIELD_SAVE_BUTTON_SELECTOR;

        $I->click(FormPage::$ADD_NEW_FIELD_BUTTON_TEXT);
        $I->waitForElementVisible($fieldType, 10);
        $I->click($fieldType);
        $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_SELECTOR, 10);
        $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_BODY_SELECTOR, 10);
        $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_HEADER_SELECTOR, 10);

        // Keep semantic check that expected field editor was loaded.
        $I->waitForText($modalHeader, 10, FormPage::$FORM_COMPONENT_MODAL_SELECTOR);

        // Prefer stable id selector with name-based fallback in one selector.
        $I->waitForElementVisible($labelSelector, 10);
        $I->fillField($labelSelector, $label);
        $I->waitForElementClickable($saveButtonSelector, 10);
        $I->click($saveButtonSelector);
        $I->waitForElementNotVisible(FormPage::$FORM_COMPONENT_MODAL_BODY_SELECTOR, 10);
    }
}
