<?php

namespace App\Tests\src\MeilSearch;

use App\MeiliSearch\MeiliSearchService;
use Exception;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Meilisearch\Exceptions\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

class MeilSearchServiceTest extends KernelTestCase
{
    use RefreshDatabaseTrait;

    protected ?Container $container;
    protected ?MeiliSearchService $meilSearchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = static::getContainer();
        $meili_url = $this->container->getParameter('meilisearch_url');
        $meili_api_key = $this->container->getParameter('meilisearch_api_key');
        $meili_prefix = $this->container->getParameter('meilisearch_prefix');

        $this->meilSearchService = new MeiliSearchService(
            $meili_url,
            $meili_api_key,
            $meili_prefix,
        );
    }
    /**
     * @dataProvider getIndexForSearch
     */
    public function testSearchMeili(?string $index_name): void
    {
        if ($index_name) {
            $this->meilSearchService->setIndexName($index_name);
        } else {
            $this->expectException(Exception::class);
        }
        $query = $index_name == 'user' ? 'test' : 'unique';
        $hits = $this->meilSearchService->search($query);
        switch ($index_name) {
            case 'offreEmploi':
                $this->assertStringContainsString("<em>", $hits['hits'][0]['_formatted']['secteurActivite']['type_secteur']);
                $this->assertStringContainsString("<em>", $hits['hits'][0]['_formatted']['secteurActivite']['category']['nom_category']);
                break;
            case 'user':
                $this->assertStringContainsString("<em>", $hits['hits'][0]['_formatted']['email']);
            default:
                break;
        }
    }

    public function testGetIndexNames(): void
    {
        $indexes = $this->meilSearchService->getIndexNames();

        $this->assertTrue(count($indexes) > 0);
    }

    /**
     * @dataProvider getIndexForSearch
     */
    public function testGetIndexName(?string $index_name): void
    {
        $this->meilSearchService->setIndexName($index_name);
        if ($index_name) {
            $this->assertEquals($index_name, $this->meilSearchService->getIndexName());
        } else {
            $this->expectException(InvalidArgumentException::class);
        }
    }
    /**
     * @dataProvider listIndexName
     */
    public function testSetIndexName(bool $is_valid, string $index_name): void
    {
        if (!$is_valid) {
            $this->expectException(InvalidArgumentException::class);
        }
        $this->meilSearchService->setIndexName($index_name);
        if ($is_valid) {
            $this->assertEquals($index_name, $this->meilSearchService->getIndexName());
        }
    }
    /**
     * @dataProvider listIndexName
     */
    public function testCheckIndexName(bool $is_valid, string $index_name): void
    {
        if ($is_valid) {
            $this->assertTrue($this->meilSearchService->checkIndexName($index_name)['is_index_name_valid']);
        } else {
            $this->assertFalse($this->meilSearchService->checkIndexName($index_name)['is_index_name_valid']);
            $this->assertStringContainsString('offreEmploi', $this->meilSearchService->checkIndexName($index_name)['list_index_valid']);
        }
    }

    public function testGetOptions(): void
    {
        $this->assertEquals([
            'attributesToHighlight' => ['*'],
            'highlightPreTag' => '<em>',
            'highlightPostTag' => '</em>'
        ], $this->meilSearchService->getOptions());
    }

    public function testSetOptions(): void
    {
        $this->meilSearchService->setOptions(['sort' => ['id:desc']]);
        $this->assertEquals([
            'attributesToHighlight' => ['*'],
            'highlightPreTag' => '<em>',
            'highlightPostTag' => '</em>',
            'sort' => ['id:desc']
        ], $this->meilSearchService->getOptions());
    }

    public static function listIndexName(): array
    {
        return [
            'invalid' => ['is_valid' => false, 'index_name' => 'other'],
            'user_index_valid' => ['is_valid' => true, 'index_name' => 'user'],
            'society_index_valid' => ['is_valid' => true, 'index_name' => 'society'],
            'offre_emploi_index_valid' => ['is_valid' => true, 'index_name' => 'offreEmploi'],

        ];
    }

    public static function getIndexForSearch(): array
    {
        return [
            'with_index_user' => ['index_name' => 'user'],
            'with_index_offreEmploi' => ['index_name' => 'offreEmploi'],
        ];
    }
}
