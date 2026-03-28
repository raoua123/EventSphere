<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
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
        $user = new User();
        $user->setEmail('api-test@example.com');
        $user->setRoles(['ROLE_USER']);

        $token = static::getContainer()
            ->get('lexik_jwt_authentication.jwt_manager')
            ->create($user);

        $this->client->request('GET', '/api/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('api-test@example.com', $response['email']);
    }

    public function testPublicEventsAreAccessibleWithoutAuth(): void
    {
        $this->client->request('GET', '/api/events');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateEventRequiresAdminRole(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);

        $token = static::getContainer()
            ->get('lexik_jwt_authentication.jwt_manager')
            ->create($user);

        $this->client->request('POST', '/api/events', [], [], [
            'CONTENT_TYPE'     => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'title'       => 'Test Event',
            'description' => 'Description',
            'date'        => '2027-06-01 10:00',
            'location'    => 'Sousse',
            'seats'       => 100,
        ]));

        $this->assertResponseStatusCodeSame(403);
    }
}
