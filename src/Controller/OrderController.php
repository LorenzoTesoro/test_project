<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use App\Service\OrderService;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderService $orderService,
    ) {}


    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            return $this->orderService->createOrder($request);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            return $this->orderService->updateOrder($request, $id);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            return $this->orderService->deleteOrder($id);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/orders', name: 'order_list', methods: ['GET'])]
    public function listOrders(Request $request): Response
    {
        $orders = $this->orderService->viewOrder($request);

        $orders = array_map(function (Order $order) {
            return [
                'id' => $order->getId(),
                'name' => $order->getName(),
                'description' => $order->getDescription(),
                'order_date' => $order->getOrderDate()->format('Y-m-d'),
                'products' => array_map(function (Product $product) {
                    return [
                        'id' => $product->getId(),
                        'price' => $product->getPrice()
                    ];
                }, $order->getProducts()->toArray())
            ];
        }, $orders);

        return new JsonResponse([
            'data' => $orders
        ], Response::HTTP_OK);
    }
}
