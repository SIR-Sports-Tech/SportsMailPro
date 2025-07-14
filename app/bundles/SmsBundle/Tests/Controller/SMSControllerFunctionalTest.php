<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\SmsBundle\Entity\Sms;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SMSControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://localhost';
        parent::setUp();
    }

    public function testSmsWithProject(): void
    {
        $sms = $this->CreateSms();

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/sms/edit/'.$sms->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['sms[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedSms = $this->em->find(Sms::class, $sms->getId());
        Assert::assertSame($project->getId(), $savedSms->getProjects()->first()->getId());
    }

    public function testSmsListView(): void
    {
        $this->setupTwilio();

        $sms = $this->createSms('ABC', 'content of sms', 'list');

        $this->em->persist($sms);
        $this->em->flush();
        $this->em->detach($sms);

        $this->client->request('GET', '/s/sms');
        $clientResponse  = $this->client->getResponse();
        $responseContent = $clientResponse->getContent();
        $this->assertTrue($clientResponse->isOk());

        $routeAlias = 'sms';
        $column     = 'stats';
        $this->assertStringContainsString(
            'col-'.$routeAlias.'-'.$column,
            $responseContent,
            'The return must contain the stats column'
        );

        $this->assertStringContainsString(
            'sms_sent:'.$sms->getId(),
            $responseContent,
            'The return must contain sms_sent:1'
        );

        $this->assertStringNotContainsString(
            'sms_delivered:1'.$sms->getId(),
            $responseContent,
            'The return must not contain sms_sent:1'
        );

        $this->assertStringNotContainsString(
            'sms_read:1'.$sms->getId(),
            $responseContent,
            'The return must not contain sms_read:1'
        );

        $this->assertStringNotContainsString(
            'sms_failed:1'.$sms->getId(),
            $responseContent,
            'The return must not contain sms_failed:1'
        );
    }

    private function CreateSms(string $name = 'sms', string $message = 'sms body'): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        $sms->setSmsType('template');
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }

    private function setupTwilio(): void
    {
        $integration = static::getContainer()->get('mautic.integration.twilio');
        $crawler     = $this->client->request(Request::METHOD_GET, 's/plugins/config/'.$integration->getName());
        $response    = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $saveButton = $crawler->selectButton('integration_details[buttons][save]');
        $form       = $saveButton->form();

        $form['integration_details[apiKeys][username]']->setValue('test_username');
        $form['integration_details[apiKeys][password]']->setValue('test_password');
        $form['integration_details[isPublished]']->setValue('1');
        $form['integration_details[featureSettings][messaging_service_sid]']->setValue('messaging_sid');

        $this->client->submit($form);

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
    }
}
