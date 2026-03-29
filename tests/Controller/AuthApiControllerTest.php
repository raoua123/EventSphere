<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }
    private function createUserAndToken(array $roles = ['ROLE_USER']): string
    {
        $user = new User();
        $user->setEmail(uniqid('test_') . '@example.com');
        $user->setRoles($roles);
        $user->setEmailVerified(true); // IMPORTANT

        $this->em->persist($user);
        $this->em->flush();

        return static::getContainer()
            ->get('lexik_jwt_authentication.jwt_manager')
            ->create($user);
    }

    public function testRegisterOptionsReturnsChallenge(): void
    {
        $this->client->request('POST', '/api/auth/register/options', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'test@example.com']));

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('challenge', $response);
        $this->assertArrayHasKey('rp', $response);
        $this->assertArrayHasKey('user', $response);
    }

    public function testRegisterOptionsRejectsMissingEmail(): void
    {
        $this->client->request('POST', '/api/auth/register/options', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testMeEndpointRequiresAuth(): void
    {
        $this->client->request('GET', '/api/auth/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeEndpointWithValidToken(): void
    {
        $token = $this->createUserAndToken();

        $this->client->request('GET', '/api/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $response);
    }

    public function testPublicEventsAreAccessibleWithoutAuth(): void
    {
        $this->client->request('GET', '/api/events');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateEventRequiresAdminRole(): void
    {
        $token = $this->createUserAndToken(['ROLE_USER']); // NOT ADMIN

        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'title'       => 'Test Event',
            'description' => 'Description',
            'date'        => '2027-06-01 10:00',
            'location'    => 'Sousse',
            'seats'       => 100,
            'image'       => 'https://example.com/image.jpg', // IMPORTANT FIX
        ]));

        $this->assertResponseStatusCodeSame(403);
    }
}