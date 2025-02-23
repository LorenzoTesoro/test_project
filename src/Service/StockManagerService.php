<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

class StockManagerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $lockfactory,
    ) {}

    public function processOrderCreation(Order $order): void
    {
        foreach ($order->getProducts() as $product) {
            $lock = $this->lockfactory->createLock('product_' . $product->getId());

            if (!$lock->acquire()) {
                throw new \RuntimeException('Could not acquire lock for product');
            }

            try {
                $this->entityManager->refresh($product); // needed for get latest quantity

                if ($product->getStockLevel() < 1) {
                    throw new \InvalidArgumentException('Product out of stock');
                }

                $product->decreaseStock(1);
                $this->entityManager->persist($product);
            } finally {
                $lock->release();
            }
        }

        $this->entityManager->flush();
    }

    public function processOrderCancellation(Order $order): void
    {
        foreach ($order->getProducts() as $product) {
            $lock = $this->lockfactory->createLock('product_' . $product->getId());

            if (!$lock->acquire()) {
                throw new \RuntimeException('Could not acquire lock for product');
            }

            try {
                $this->entityManager->refresh($product);
                $product->increaseStock(1);
                $this->entityManager->persist($product);
            } finally {
                $lock->release();
            }
        }

        $this->entityManager->flush();
    }

    public function validateStock(array $productQuantities): bool
    {
        foreach ($productQuantities as $productId => $quantity) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);

            if (!$product || $product->getStockLevel() < $quantity) {
                return false;
            }
        }

        return true;
    }
}
