<?php

namespace App\Tests\src\State;

use ApiPlatform\Metadata\Post;
use App\Entity\User;
use App\State\ProfilUserProcessor;
use App\Traits\FixturesTrait;
use App\Traits\LogUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProfilUserProcessorTest extends KernelTestCase
{
    use ReloadDatabaseTrait;
    use LogUserTrait;
    use FixturesTrait;

    private ?JWTTokenManagerInterface $jWTTokenManager;
    private ?EntityManagerInterface $em;
    private ?Security $security;
    private ?ValidatorInterface $validator;
    private ?ProfilUserProcessor $profilUserProcessor;

    public function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->jWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);


        $this->loadFixturesTrait();

        $this->profilUserProcessor = new ProfilUserProcessor(
            $this->jWTTokenManager,
            $this->security,
            $this->em,
            $this->validator
        );
    }
    /**
     * @dataProvider provideDataProfil
     */
    public function testProfilUserProcess($data, $file): void
    {
        /** @var User $user_1 */
        $user_1 = $this->all_fixtures['user_1'];

        $this->logUserTrait($user_1);

        $request = new Request([], [], [], [], [], []);
        $request->headers->set("content-type", "multipart/form-data");
        foreach ($data as $prop => $v) {
            $request->request->set($prop, $v);
        }

        if ($file['file']) {
            $path_file = static::getContainer()->getParameter('path_source_image_test') . 'test.png';
            $tmp_file = sys_get_temp_dir() . '/test_uploaded_file.png';
            copy($path_file, $tmp_file);
            $uploadedFile = new UploadedFile($tmp_file, 'test.png', null, null, true);
            $request->files->set('file', $uploadedFile);
        }

        $post = new Post();
        /** @var JsonResponse $user_process */
        $json_response = $this->profilUserProcessor->process(null, $post, [], ['request' => $request]);

        /**
         * verifie si le fichier est bien uploader
         * puis on l'efface
         */
        if ($file['file']) {
            $path_dest = static::getContainer()->getParameter('path_dest_images_test');
            $file_name = null;
            foreach (scandir($path_dest) as $r) {
                if ($r != ".." and $r != ".") {
                    if (str_starts_with($r, "test") and str_ends_with($r, ".png")) {
                        $file_name = $path_dest . '' . $r;
                    }
                }
            }

            $this->assertFileExists($file_name);
            unlink($file_name);
            $this->assertFileDoesNotExist($file_name);
        }

        $user_bdd = $this->em->getRepository(User::class)->find($user_1->getId());


        $this->assertEquals("username", $user_1->getUsername());
        $this->assertEquals("email@email.com", $user_1->getEmail());
        $this->assertEquals("NOM", $user_1->getNom());
        $this->assertEquals("Prenom", $user_1->getPrenom());

        $this->assertNotNull($user_bdd);
        $this->assertArrayHasKey("token", json_decode($json_response->getContent(), true));
    }

    public static function provideDataProfil(): array
    {
        return [
            "data without file" => [
                [
                    "username" => "username",
                    "email" => "email@email.com",
                    "nom" => "nom",
                    "prenom" => "prenom"
                ],
                [
                    'file' => false,
                ]
            ],
            "data with file" => [
                [
                    "username" => "username",
                    "email" => "email@email.com",
                    "nom" => "nom",
                    "prenom" => "prenom"
                ],
                [
                    'file' => true,
                ]
            ]
        ];
    }

    public function testSetProperties(): void
    {
        $user = new User();
        $data = [
            'nom' => "nom",
            "prenom" => "prenom",
            "email" => "email@email.com"
        ];

        $refMethod = new \ReflectionMethod(ProfilUserProcessor::class, 'setProperties');
        $refMethod->setAccessible(true);

        $user = $refMethod->invoke($this->profilUserProcessor, $user, $data);

        $this->assertEquals('NOM', $user->getNom());
        $this->assertEquals('Prenom', $user->getPrenom());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em = null;
        $this->jWTTokenManager = null;
        $this->security = null;
        $this->profilUserProcessor = null;
    }
}
