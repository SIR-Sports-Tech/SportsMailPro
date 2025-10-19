<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignProjectAuditLogTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private function createProject(string $name): Project
    {
        $project = new Project();
        $project->setName($name);
        $this->em->persist($project);

        return $project;
    }

    private function createForm(string $name): Form
    {
        $form = new Form();
        $form->setName($name);
        $this->em->persist($form);

        return $form;
    }

    public function testCampaignProjectAuditLog(): void
    {
        // 1. Create a campaign.
        $campaign = $this->createCampaign('Project Audit Log Campaign');
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        $campaignId = $campaign->getId();

        // 2. Create a project and add it to the campaign to generate audit log.
        $project = $this->createProject('Test Project');

        // Add the project to the campaign through the model to trigger audit log creation.
        $campaignModel = static::getContainer()->get('mautic.campaign.model.campaign');
        $campaign      = $campaignModel->getEntity($campaignId);
        $campaign->addProject($project);
        $campaignModel->saveEntity($campaign);
        $this->em->clear();

        // 3. View the campaign to check audit log rendering.
        $campaignViewUrl = '/s/campaigns/view/'.$campaignId;
        $this->client->request(Request::METHOD_GET, $campaignViewUrl);
        $this->assertResponseIsSuccessful();

        $translator = static::getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        // Verify that the projects changelog message appears.
        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.changelog.projects.updated'),
            $this->client->getResponse()->getContent()
        );

        // Verify that the project name appears in the audit log.
        $this->assertStringContainsString(
            'Test Project',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCampaignFormAuditLog(): void
    {
        // 1. Create a campaign.
        $campaign = $this->createCampaign('Form Audit Log Campaign');
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        $campaignId = $campaign->getId();

        // 2. Create a form and add it to the campaign to generate audit log.
        $form = $this->createForm('Test Form');

        // Add the form to the campaign through the model to trigger audit log creation.
        $campaignModel = static::getContainer()->get('mautic.campaign.model.campaign');
        $campaign      = $campaignModel->getEntity($campaignId);
        $campaign->addForm($form);
        $campaignModel->saveEntity($campaign);
        $this->em->clear();

        // 3. View the campaign to check audit log rendering.
        $campaignViewUrl = '/s/campaigns/view/'.$campaignId;
        $this->client->request(Request::METHOD_GET, $campaignViewUrl);
        $this->assertResponseIsSuccessful();

        $translator = static::getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        // Verify that the forms changelog message appears.
        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.changelog.forms.updated'),
            $this->client->getResponse()->getContent()
        );

        // Verify that the form name appears in the audit log.
        $this->assertStringContainsString(
            'Test Form',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCampaignLeadsAuditLog(): void
    {
        // 1. Create a campaign.
        $campaign = $this->createCampaign('Leads Audit Log Campaign');
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        $campaignId = $campaign->getId();

        // 2. Create a contact/lead and add it to the campaign to generate audit log.
        $lead = $this->createLead('John', 'Doe', 'john.doe@example.com');

        $this->em->flush();

        // Add the lead to the campaign through the model to trigger audit log creation.
        $campaignModel = static::getContainer()->get('mautic.campaign.model.campaign');
        $campaign      = $campaignModel->getEntity($campaignId);

        // Create a campaign lead entity
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());

        $campaign->addLead(0, $campaignLead);
        $campaignModel->saveEntity($campaign);
        $this->em->clear();

        // 3. View the campaign to check audit log rendering.
        $campaignViewUrl = '/s/campaigns/view/'.$campaignId;
        $this->client->request(Request::METHOD_GET, $campaignViewUrl);
        $this->assertResponseIsSuccessful();

        $translator = static::getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        // Verify that the leads changelog message appears.
        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.changelog.leads.updated'),
            $this->client->getResponse()->getContent()
        );
    }
}
