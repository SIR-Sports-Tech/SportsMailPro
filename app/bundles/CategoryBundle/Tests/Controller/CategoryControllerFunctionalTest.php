<?php

namespace Mautic\CategoryBundle\Tests\Controller;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryControllerFunctionalTest extends MauticMysqlTestCase
{
    private TranslatorInterface $translator;

    /**
     * Create two new categories.
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $categoriesData = [
            [
                'title'  => 'TestTitleCategoryController1',
                'bundle' => 'page',
            ],
            [
                'title'  => 'TestTitleCategoryController2',
                'bundle' => 'global',
            ],
        ];
        /** @var CategoryModel $model */
        $model      = static::getContainer()->get('mautic.category.model.category');

        foreach ($categoriesData as $categoryData) {
            $category = new Category();
            $category->setIsPublished(true)
                ->setTitle($categoryData['title'])
                ->setBundle($categoryData['bundle']);
            $model->saveEntity($category);
        }

        $this->translator = static::getContainer()->get('translator');
    }

    /**
     * Get all results without filtering.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/categories?tmpl=list&bundle=category');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('TestTitleCategoryController1', $clientResponseContent, 'The return must contain TestTitleCategoryController1');
        $this->assertStringContainsString('TestTitleCategoryController2', $clientResponseContent, 'The return must contain TestTitleCategoryController2');
    }

    /**
     * Get a result with filter.
     */
    public function testIndexActionWhenFiltered(): void
    {
        $this->client->request('GET', '/s/categories/page?tmpl=list&bundle=page');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('TestTitleCategoryController1', $clientResponseContent, 'The return must contain TestTitleCategoryController1');
        $this->assertStringNotContainsString('TestTitleCategoryController2', $clientResponseContent, 'The return must not contain TestTitleCategoryController2');
    }

    public function testNewActionWithInForm(): void
    {
        $crawler                = $this->client->request(Request::METHOD_GET, 's/categories/category/new');
        $clientResponse         = json_decode($this->client->getResponse()->getContent(), true);
        $html                   = $clientResponse['newContent'];
        $crawler->addHtmlContent($html);
        $saveButton = $crawler->selectButton('category_form[buttons][save]');
        $form       = $saveButton->form();
        $form['category_form[bundle]']->setValue('category');
        $form['category_form[title]']->setValue('Test');
        $form['category_form[isPublished]']->setValue('1');
        $form['category_form[inForm]']->setValue('1');

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());
        $clientResponse = $this->client->getResponse();
        $body           = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('categoryId', $body);
        $this->assertArrayHasKey('categoryName', $body);
    }

    public function testEditLockCategory(): void
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel      = static::getContainer()->get('mautic.category.model.category');
        /** @var UserModel $userModel */
        $userModel      = static::getContainer()->get('mautic.user.model.user');
        $user           = $userModel->getEntity(2);

        $category = new Category();
        $category->setTitle('New Category');
        $category->setAlias('category');
        $category->setBundle('global');
        $category->setCheckedOutBy($user);
        $category->setCheckedOut(new \DateTime('now'));
        $categoryModel->saveEntity($category, false);

        $this->client->request(Request::METHOD_GET, 's/categories/category/edit/'.$category->getId());
        $this->assertStringContainsString('is currently checked out by', $this->client->getResponse()->getContent());
    }

    public function testEditCategorySaveAndClose(): void
    {
        /** @var CategoryModel $categoryModel */
        $categoryModel = static::getContainer()->get('mautic.category.model.category');

        // Create a test category
        $category = new Category();
        $category->setTitle('Test Category for Edit');
        $category->setAlias('test-category-edit');
        $category->setBundle('global');
        $category->setIsPublished(true);
        $categoryModel->saveEntity($category);

        // Test the edit form submission (save and close)
        $crawler = $this->client->request(Request::METHOD_GET, 's/categories/global/edit/'.$category->getId());
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // Find the form and submit it
        $form = $crawler->selectButton('category_form[buttons][save]')->form();
        $form['category_form[title]']->setValue('Updated Test Category');
        $form['category_form[description]']->setValue('Updated description');

        $this->client->submit($form);

        // Verify the response is successful (no OutOfBoundsException)
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Verify the category was updated
        $updatedCategory = $categoryModel->getEntity($category->getId());
        $this->assertSame('Updated Test Category', $updatedCategory->getTitle());
        $this->assertSame('Updated description', $updatedCategory->getDescription());
    }

    public function testDeleteUsedInStage(): void
    {
        $category = new Category();
        $category->setIsPublished(true);
        $category->setTitle('Category for stage');
        $category->setDescription('Category for stage');
        $category->setBundle('global');
        $category->setAlias('category-for-stage');
        $this->em->persist($category);

        $stage = new Stage();
        $stage->setName('test for category');
        $stage->setCategory($category);
        $stage->setDescription('Random Stage Description');
        $stage->setWeight(10);
        $this->em->persist($stage);
        $this->em->flush();

        $expectedErrorMessage = $this->translator->trans(
            'mautic.category.is_in_use.delete',
            [
                '%entities%'      => 'Stage Id: '.$stage->getId(),
                '%categoryName%'  => $category->getTitle(),
            ],
            'validators'
        );

        $this->client->request('POST', 's/categories/category/delete/'.$category->getId(), [], [], [
            'HTTP_Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token'     => $this->getCsrfToken('mautic_ajax_post'),
        ]);

        $clientResponse = $this->client->getResponse();
        $this->assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $clientResponse->getStatusCode(),
            $clientResponse->getContent()
        );
        $clientResponseBody = json_decode($clientResponse->getContent(), true);

        $this->assertStringContainsString($expectedErrorMessage, $clientResponseBody['flashes']);
    }

    public function testBatchDeleteUsedInStage(): void
    {
        $category = new Category();
        $category->setIsPublished(true);
        $category->setTitle('Category for stage');
        $category->setDescription('Category for stage');
        $category->setBundle('global');
        $category->setAlias('category-for-stage');
        $this->em->persist($category);

        $stage = new Stage();
        $stage->setName('test for category');
        $stage->setCategory($category);
        $stage->setDescription('Random Stage Description');
        $stage->setWeight(10);
        $this->em->persist($stage);
        $this->em->flush();

        $expectedErrorMessage = $this->translator->trans(
            'mautic.category.is_in_use.delete',
            [
                '%entities%'      => 'Stage Id: '.$stage->getId(),
                '%categoryName%'  => $category->getTitle(),
            ],
            'validators'
        );

        $parameters = 'ids=["'.$category->getId().'"]';
        $this->client->request('POST', 's/categories/category/batchDelete?'.$parameters, [], [], [
            'HTTP_Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token'     => $this->getCsrfToken('mautic_ajax_post'),
        ]);

        $clientResponse = $this->client->getResponse();

        $clientResponseBody = json_decode($clientResponse->getContent(), true);

        $this->assertStringContainsString($expectedErrorMessage, $clientResponseBody['flashes']);
    }
}
