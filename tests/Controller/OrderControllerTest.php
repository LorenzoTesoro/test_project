<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use OrderService;

class OrderControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    public function __construct(private readonly OrderService $orderService) {}

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
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
    private function createOrderWithProducts(string $name, string $description, string $date, array $productPrices): Order
    {
        $order = new Order();
        $order->setName($name);
        $order->setDescription($description);
        $order->setOrderDate(new \DateTime($date));

        foreach ($productPrices as $price) {
            $product = $this->createProduct($price);
            $order->addProduct($product);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function createProduct(float $price): Product
    {
        $product = new Product();
        $product->setPrice($price);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
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
        $initialResponse = $this->createOrder();
        $orderId = $initialResponse['id'];

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

    public function testDeleteOrderSuccess(): void
    {
        $response = $this->createOrder();
        $orderId = $response['id'];

        $this->client->request(
            'DELETE',
            "/api/orders/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData  = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Order deleted successfully',  $responseData['message']);

        // Optional: You can also assert that the order no longer exists by trying to retrieve it
        /*  $this->client->request('GET', "/api/orders/{$orderId}");
        $getResponse = $this->client->getResponse(); */

        // Assert that fetching the deleted order results in a 404 (not found)
        //$this->assertEquals(Response::HTTP_NOT_FOUND, $getResponse->getStatusCode());
    }

    public function testListOrdersWithProducts(): void
    {
        // Arrange
        $order1 = $this->createOrderWithProducts(
            'Test Order 1',
            'Description 1',
            '2024-02-20',
            [29.99, 49.99]
        );

        $order2 = $this->createOrderWithProducts(
            'Test Order 2',
            'Description 2',
            '2024-02-21',
            [19.99, 39.99, 59.99]
        );

        // Act
        $this->client->request('GET', '/api/orders/orders');

        // Assert
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(2, $responseData['data']);

        // Verify first order
        $firstOrder = $responseData['data'][0];
        $this->assertEquals('Test Order 1', $firstOrder['name']);
        $this->assertCount(2, $firstOrder['products']);

        // Verify second order
        $secondOrder = $responseData['data'][1];
        $this->assertEquals('Test Order 2', $secondOrder['name']);
        $this->assertCount(3, $secondOrder['products']);
    }

    public function testListOrdersWithNameFilter(): void
    {
        // Arrange
        $this->createOrderWithProducts(
            'Special Order',
            'Description 1',
            '2024-02-20',
            [29.99, 49.99]
        );

        $this->createOrderWithProducts(
            'Regular Order',
            'Description 2',
            '2024-02-21',
            [19.99]
        );

        // Act
        $this->client->request('GET', '/api/orders/orders?name=Special');

        // Assert
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Special Order', $responseData['data'][0]['name']);
        $this->assertCount(2, $responseData['data'][0]['products']);
    }

    public function testListOrdersWithDateFilter(): void
    {
        // Arrange
        $this->createOrderWithProducts(
            'Early Order',
            'Description 1',
            '2024-02-01',
            [29.99]
        );

        $this->createOrderWithProducts(
            'Mid Order',
            'Description 2',
            '2024-02-15',
            [19.99, 39.99]
        );

        $this->createOrderWithProducts(
            'Late Order',
            'Description 3',
            '2024-02-28',
            [49.99]
        );

        // Act
        $this->client->request('GET', '/api/orders/orders?start_date=2024-02-10&end_date=2024-02-20');

        // Assert
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Mid Order', $responseData['data'][0]['name']);
        $this->assertCount(2, $responseData['data'][0]['products']);
    }

    public function testListOrdersWithMultipleFilters(): void
    {
        // Arrange
        $this->createOrderWithProducts(
            'Special Order',
            'Unique Description',
            '2024-02-15',
            [29.99, 49.99]
        );

        $this->createOrderWithProducts(
            'Special Order',
            'Different Description',
            '2024-02-15',
            [19.99]
        );

        // Act
        $this->client->request('GET', '/api/orders/orders?name=Special&description=Unique');

        // Assert
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Special Order', $responseData['data'][0]['name']);
        $this->assertEquals('Unique Description', $responseData['data'][0]['description']);
        $this->assertCount(2, $responseData['data'][0]['products']);
    }
}
