<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignProjectAuditLogTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

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

        $this->em->flush();
        $this->em->clear();

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

        $this->em->flush();
        $this->em->clear();

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
}
