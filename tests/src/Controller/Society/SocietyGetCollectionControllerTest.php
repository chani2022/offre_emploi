<?php

namespace App\Tests\src\Controller\Society;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Traits\FixturesTrait;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

class SocietyGetCollectionControllerTest extends ApiTestCase
{
    use RefreshDatabaseTrait;
    use FixturesTrait;

    private ?Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->loadFixturesTrait();
    }
    /**
     * @dataProvider getUsers
     */
    public function testAuthorizedShowCollectionSociety(?string $roles): void
    {
        $user_load = match ($roles) {
            "super" => $this->all_fixtures['super'],
            "admin" => $this->all_fixtures['admin'],
            "admin_not_access" => $this->all_fixtures['admin_1'],
            "user" => $this->all_fixtures['user_activate_society'],
            default => null
        };

        if ($user_load) {
            $this->client->loginUser($user_load);
        }
        $this->client->request('GET', '/api/societies');

        $this->assert($roles);
    }

    public static function getUsers(): array
    {
        return [
            "super_admin" => [
                "roles" => "super",
            ],
            "admin" => [
                "roles" => "admin",
            ],
            "admin_not_access" => [
                "roles" => "admin_not_access",
            ],
            "user" => [
                "roles" => "user",
            ],
            "anonymous" => [
                "roles" => null
            ]
        ];
    }

    private function assert($roles): void
    {
        if ($roles == "super") {
            $this->assertResponseIsSuccessful();
        } else {
            $status = 403;
            if (is_null($roles)) {
                $status = 401;
            }
            $this->assertResponseStatusCodeSame($status);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client = null;
    }
}
