<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OrderControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testCreateOrder(): void
    {
        $data = [
            'name' => 'Test Order',
            'description' => 'This is a test order',
            'order_date' => '2025-02-22',
        ];

        $this->client->request(Request::METHOD_POST, '/api/orders', [], [], [], json_encode($data));

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('Test Order', $responseData['name']);
        $this->assertEquals('This is a test order', $responseData['description']);
        $this->assertEquals('2025-02-22', $responseData['order_date']);
        $this->assertNotNull($responseData['order_date']);
    }
}
