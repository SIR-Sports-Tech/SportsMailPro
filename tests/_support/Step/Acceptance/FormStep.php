<?php

namespace Step\Acceptance;

use Facebook\WebDriver\Exception\TimeoutException;
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

        $I->waitForElementVisible(FormPage::$ADD_NEW_FIELD_TRIGGER_SELECTOR, 10);

        // Prefer a deterministic path: trigger the modal from the underlying select option.
        $modalTriggered = $this->triggerFieldTypeSelectionByLabel($modalHeader);

        if (true !== $modalTriggered) {
            // Fallback to the chosen UI interaction path.
            $I->click(FormPage::$ADD_NEW_FIELD_TRIGGER_SELECTOR);
            $I->waitForElementVisible($fieldType, 10);
            $I->click($fieldType);
        }

        try {
            $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_SELECTOR, 20);
        } catch (TimeoutException) {
            // Retry deterministic path first in case the first trigger race-lost with UI rendering.
            $modalTriggered = $this->triggerFieldTypeSelectionByLabel($modalHeader);

            if (true !== $modalTriggered) {
                // Keep the UI retry as a final fallback.
                $I->waitForElementVisible(FormPage::$ADD_NEW_FIELD_TRIGGER_SELECTOR, 10);
                $I->click(FormPage::$ADD_NEW_FIELD_TRIGGER_SELECTOR);
                $I->waitForElementVisible($fieldType, 10);
                $I->click($fieldType);
            }

            $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_SELECTOR, 20);
        }

        $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_BODY_SELECTOR, 20);
        $I->waitForElementVisible(FormPage::$FORM_COMPONENT_MODAL_HEADER_SELECTOR, 20);

        // Keep semantic check that expected field editor was loaded.
        $I->waitForText($modalHeader, 20, FormPage::$FORM_COMPONENT_MODAL_SELECTOR);

        // Prefer stable id selector with name-based fallback in one selector.
        $I->waitForElementVisible($labelSelector, 20);
        $I->fillField($labelSelector, $label);
        $I->waitForElementClickable($saveButtonSelector, 10);
        $I->click($saveButtonSelector);
        $I->waitForElementNotVisible(FormPage::$FORM_COMPONENT_MODAL_BODY_SELECTOR, 10);
        $I->waitForElementNotVisible(FormPage::$FORM_COMPONENT_MODAL_SELECTOR, 10);
    }

    private function triggerFieldTypeSelectionByLabel(string $label): mixed
    {
        $encodedLabel = json_encode($label);

        return $this->executeJS(<<<JS
const label = {$encodedLabel};
const select = document.querySelector('#fields-container .available-fields select.form-builder-new-component');

if (!select) {
    return false;
}

const option = Array.from(select.options).find((opt) => opt.textContent.trim() === label);
if (!option) {
    return false;
}

if (!option.getAttribute('data-target') || !option.getAttribute('data-href')) {
    return false;
}

if (window.mQuery && window.Mautic && typeof window.Mautic.ajaxifyModal === 'function') {
    window.mQuery(option).trigger('click');
    window.Mautic.ajaxifyModal(window.mQuery(option));
    select.value = option.value;
    window.mQuery(select).trigger('change');
    window.mQuery(select).trigger('chosen:updated');
    return true;
}

select.value = option.value;
select.dispatchEvent(new Event('change', { bubbles: true }));

if (window.mQuery) {
    window.mQuery(select).trigger('chosen:updated');
}

return true;
JS);
    }
}
