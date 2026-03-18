<?php

declare(strict_types=1);

namespace Page\Acceptance;

class FormPage
{
    public static string $URL                                   = '/s/forms/new';

    public static string $FORM_NAME                             = 'Send Result';
    public static string $FORM_POST_ACTION_PROPERTY             = 'Thanks';
    public static string $ADD_NEW_FIELD_BUTTON_TEXT             = 'Add a new field';
    public static string $ADD_NEW_FIELD_TRIGGER_SELECTOR        = '#fields-container .available-fields .chosen-container a.chosen-single';
    public static string $FORM_COMPONENT_MODAL_SELECTOR         = '#formComponentModal';
    public static string $FORM_COMPONENT_MODAL_BODY_SELECTOR    = '#formComponentModal .bundle-form';
    public static string $FORM_COMPONENT_MODAL_HEADER_SELECTOR  = '#formComponentModal .bundle-form-header h3';
    public static string $FORM_FIELD_TEXT_SHORT_ANSWER_SELECTOR = '//div[contains(@class, "chosen-container") and contains(@class, "chosen-with-drop")]//li[contains(@class, "active-result") and contains(normalize-space(.), "Text: Short answer")]';
    public static string $FORM_FIELD_EMAIL_SELECTOR             = '//div[contains(@class, "chosen-container") and contains(@class, "chosen-with-drop")]//li[contains(@class, "active-result") and normalize-space(.)="Email"]';
    public static string $FORM_FIELD_LABEL_SELECTOR             = '#formComponentModal input#formfield_label, #formComponentModal input[name="formfield[label]"]';
    public static string $FORM_FIELD_SAVE_BUTTON_SELECTOR       = '#formComponentModal div.modal-footer button.btn-primary';
}
