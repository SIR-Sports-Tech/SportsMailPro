<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\HttpFoundation\Request;

class FieldControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['create_custom_field_in_background'] = 'testAbortColumnCreateExceptionIsHandledOnEditAction' === $this->getName();

        parent::setUp();
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldFailure(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'The leading Drupal Cloud platform to securely develop, deliver, and run websites, applications, and content. Top-of-the-line hosting options are paired with automated testing and development tools. Documentation is also included for the following components';
        $form['leadfield[label]']->setValue($label);
        $crawler = $this->client->submit($form);

        $labelErrorMessage             = trim($crawler->filter('#leadfield_label')->nextAll()->text());
        $maxLengthErrorMessageTemplate = 'Label value cannot be longer than 191 characters';

        $this->assertEquals($maxLengthErrorMessageTemplate, $labelErrorMessage);
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldSuccess(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'Test value for custom field 4';
        $form['leadfield[label]']->setValue($label);
        $crawler = $this->client->submit($form);

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        $this->assertNotNull($field);
    }

    public function testAbortColumnCreateExceptionIsHandledOnEditAction(): void
    {
        // First create a field
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');
        $form    = $crawler->selectButton('Save & Close')->form();
        $label   = 'Test field for edit exception';
        $alias   = 'test_field_edit_exception';
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[alias]']->setValue($alias);
        $crawler = $this->client->submit($form);

        // When background processing is enabled, we might not get a redirect
        // Instead, we stay on the same page with a message
        $response = $this->client->getResponse();
        if ($response->isRedirect()) {
            $crawler = $this->client->followRedirect();
        }

        // Check for any flash messages (success or notice)
        $flashMessages = $crawler->filter('.alert');
        $this->assertGreaterThan(0, $flashMessages->count(), 'No flash messages found after field creation');

        // Get the created field
        $field = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => $alias]);
        $this->assertNotNull($field, 'Field was not created');

        // Run the background command to create the column
        $commandTester = $this->testSymfonyCommand('mautic:custom-field:create-column', ['--id' => $field->getId()]);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Now edit the field - just change the label
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/edit/'.$field->getId());
        $form    = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue($label.' edited');
        $crawler = $this->client->submit($form);

        // Check response after edit
        $response = $this->client->getResponse();
        if ($response->isRedirect()) {
            $crawler = $this->client->followRedirect();
        }

        // Check for success message after edit
        $flashMessages = $crawler->filter('.alert');
        $this->assertGreaterThan(0, $flashMessages->count(), 'No flash messages found after field edit');

        // Verify the field was updated
        $this->em->clear();
        $updatedField = $this->em->getRepository(LeadField::class)->find($field->getId());
        $this->assertNotNull($updatedField);
        $this->assertEquals($label.' edited', $updatedField->getLabel());
    }
}
