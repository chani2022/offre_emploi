<?php

namespace App\Tests\src\Controller\NiveauEtude;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Entity\User;
use App\Traits\FixturesTrait;

class GetSecteurActiviteControllerTest extends ApiTestCase
{
    use RefreshDatabaseTrait;
    use FixturesTrait;

    private ?Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->loadFixturesTrait();
    }
    /**
     * @dataProvider getUserAuthorized
     */
    public function testGetAuthorizedSecteurActivite(?string $roles, bool $access): void
    {
        /** @var User */
        $user = $this->getUser($roles);
        if ($roles) {
            $this->client->loginUser($user);
        }

        $this->client->request('GET', '/api/secteur_activites/' . $this->all_fixtures['secteur_unique']->getId());

        if (!$access) {
            $status = 401;
            if ($roles) {
                $status = 403;
            }
            $this->assertResponseStatusCodeSame($status);
        } else {
            /** @var Category */
            $category = $this->all_fixtures['category_unique'];
            $this->assertResponseStatusCodeSame(200);
            $this->assertJsonContains([
                "type_secteur" => "UNIQUE",
                "category" => [
                    "id" => $category->getId(),
                    "nom_category" => $category->getNomCategory()
                ]
            ]);
        }
    }

    private function getUser(?string $roles): ?User
    {
        /** @var User */
        $user = match ($roles) {
            'super' => $this->all_fixtures['super'],
            'admin' => $this->all_fixtures['admin'],
            'user' => $this->all_fixtures['user_1'],
            default => null
        };

        return $user;
    }

    public static function getUserAuthorized(): array
    {
        return [
            'super' => ['roles' => 'super', 'access' => true],
            'admin' => ['roles' => 'admin', 'access' => false],
            'user' => ['roles' => 'user', 'access' => false],
            'anonymous' => ['roles' => null, 'access' => false]
        ];
    }

    public static function getData(): array
    {
        return [
            'valid' => [['niveau_etude' => 'bacc']],
            'null' => [['niveau_etude' => null]],
            'blank' => [['niveau_etude' => '']],
        ];
    }

    private function myLogUser(): void
    {
        /** @var User */
        $super = $this->all_fixtures['super'];
        $this->client->loginUser($super);
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }
}
