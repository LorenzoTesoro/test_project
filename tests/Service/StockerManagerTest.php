<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\StockManagerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class StockManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private StockManagerService $stockManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->stockManager = static::getContainer()->get(StockManagerService::class);

        // Clear database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
    }

    public function testProcessOrderCreationDecreasesStock(): void
    {
        // Arrange
        $product = new Product();
        $product->setPrice(29.99);
        $product->setStockLevel(10);

        $order = new Order();
        $order->setName('Test Order');
        $order->addProduct($product);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Act
        $this->stockManager->processOrderCreation($order);

        // Assert
        $this->entityManager->refresh($product);
        $this->assertEquals(9, $product->getStockLevel());
    }

    public function testProcessOrderCreationWithInsufficientStock(): void
    {
        // Arrange
        $product = new Product();
        $product->setPrice(29.99);
        $product->setStockLevel(0);

        $order = new Order();
        $order->setName('Test Order');
        $order->addProduct($product);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->stockManager->processOrderCreation($order);
    }

    public function testProcessOrderCancellationIncreasesStock(): void
    {
        // Arrange
        $product = new Product();
        $product->setPrice(29.99);
        $product->setStockLevel(5);

        $order = new Order();
        $order->setName('Test Order');
        $order->addProduct($product);

        $this->entityManager->persist($product);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Act
        $this->stockManager->processOrderCancellation($order);

        // Assert
        $this->entityManager->refresh($product);
        $this->assertEquals(6, $product->getStockLevel());
    }

    public function testValidateStockWithSufficientStock(): void
    {
        // Arrange
        $product = new Product();
        $product->setPrice(29.99);
        $product->setStockLevel(10);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Act
        $result = $this->stockManager->validateStock([$product->getId() => 5]);

        // Assert
        $this->assertTrue($result);
    }

    public function testValidateStockWithInsufficientStock(): void
    {
        // Arrange
        $product = new Product();
        $product->setPrice(29.99);
        $product->setStockLevel(3);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Act
        $result = $this->stockManager->validateStock([$product->getId() => 5]);

        // Assert
        $this->assertFalse($result);
    }
}
