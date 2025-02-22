<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class OrderControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clear the database before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
    }

    private function createOrder(array $data = []): array
    {
        $defaultData = [
            'name' => 'Test Order',
            'description' => 'This is a test order',
            'order_date' => '2025-02-22',
        ];

        $orderData = array_merge($defaultData, $data);

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testCreateOrderSuccess(): void
    {
        $data = [
            'name' => 'New Order',
            'description' => 'Test description',
            'order_date' => '2025-03-01',
        ];

        $response = $this->createOrder($data);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($data['name'], $response['name']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['order_date'], $response['order_date']);
    }

    public function testCreateOrderWithMinimalData(): void
    {
        $data = [
            'name' => 'Minimal Order',
        ];

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Minimal Order', $responseData['name']);
        $this->assertArrayHasKey('order_date', $responseData);
    }

    public function testCreateOrderWithInvalidDate(): void
    {
        $data = [
            'name' => 'Invalid Date Order',
            'order_date' => 'invalid-date',
        ];

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreateOrderWithEmptyName(): void
    {
        $data = [
            'name' => '',
            'order_date' => '2025-02-22',
        ];

        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testEditOrderSuccess(): void
    {
        // Create initial order
        $response = $this->createOrder();
        $orderId = $response['id'];

        $updateData = [
            'name' => 'Updated Order',
            'description' => 'Updated description',
            'order_date' => '2025-03-01',
        ];

        $this->client->request(
            'PUT',
            "/api/orders/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($updateData['name'], $responseData['name']);
        $this->assertEquals($updateData['description'], $responseData['description']);
        $this->assertEquals($updateData['order_date'], $responseData['order_date']);
    }

    public function testPartialOrderUpdate(): void
    {
        // Create initial order
        $initialResponse = $this->createOrder();
        $orderId = $initialResponse['id'];

        // Update only the name
        $updateData = [
            'name' => 'Partially Updated Order',
        ];

        $this->client->request(
            'PUT',
            "/api/orders/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($updateData['name'], $responseData['name']);
        $this->assertEquals($initialResponse['description'], $responseData['description']);
        $this->assertEquals($initialResponse['order_date'], $responseData['order_date']);
    }

    public function testEditNonExistentOrder(): void
    {
        $nonExistentId = 99999;
        $updateData = [
            'name' => 'Updated Order',
        ];

        $this->client->request(
            'PUT',
            "/api/orders/{$nonExistentId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateOrderWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testEditOrderWithInvalidJson(): void
    {
        $response = $this->createOrder();
        $orderId = $response['id'];

        $this->client->request(
            'PUT',
            "/api/orders/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testEditOrderWithInvalidDate(): void
    {
        $response = $this->createOrder();
        $orderId = $response['id'];

        $updateData = [
            'order_date' => 'invalid-date'
        ];

        $this->client->request(
            'PUT',
            "/api/orders/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreateMultipleOrders(): void
    {
        $orders = [
            ['name' => 'First Order'],
            ['name' => 'Second Order'],
            ['name' => 'Third Order'],
        ];

        foreach ($orders as $orderData) {
            $response = $this->createOrder($orderData);
            $this->assertArrayHasKey('id', $response);
            $this->assertEquals($orderData['name'], $response['name']);
        }
    }
}
