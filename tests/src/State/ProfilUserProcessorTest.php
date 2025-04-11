<?php

namespace App\Tests\src\State;

use ApiPlatform\Metadata\Post;
use App\Entity\User;
use App\State\ProfilUserProcessor;
use App\Traits\FixturesTrait;
use App\Traits\LogUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProfilUserProcessorTest extends KernelTestCase
{
    use RefreshDatabaseTrait;
    use LogUserTrait;
    use FixturesTrait;

    private ?JWTTokenManagerInterface $jWTTokenManager;
    private ?EntityManagerInterface $em;
    private ?Security $security;
    private ?ValidatorInterface $validator;

    public function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->jWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        $this->loadFixturesTrait();
    }

    public function testProfilUserProcess(): void
    {
        /** @var User $user_1 */
        $user_1 = $this->all_fixtures['user_1'];

        $this->logUserTrait($user_1);

        $data = ["username" => "username", "email" => "email@email.com"];
        $request = new Request([], [], [], [], [], [], $data);
        $request->headers->set("content-type", "multipart/form-data");
        $request->request->set("username", "username");
        $request->request->set("nom", "nom");
        $request->request->set("prenom", "prenom");
        $request->request->set("email", "email@email.com");

        $profilUserProcessor = new ProfilUserProcessor(
            $this->jWTTokenManager,
            $this->security,
            $this->em,
            $this->validator
        );

        $post = new Post();
        /** @var JsonResponse $user_process */
        $user_process = $profilUserProcessor->process(null, $post, [], ['request' => $request]);

        $user_bdd = $this->em->getRepository(User::class)->find($user_1->getId());

        $this->assertEquals("email@email.com", $user_1->getEmail());
        $this->assertEquals("username", $user_1->getUsername());

        $this->assertNotNull($user_bdd);
        $this->assertArrayHasKey("token", json_decode($user_process->getContent(), true));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em = null;
        $this->jWTTokenManager = null;
        $this->security = null;
    }
}
