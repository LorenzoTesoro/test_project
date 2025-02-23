<?php

namespace App\Controller;

use App\Entity\Order;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = new Order();

            if (isset($data['name']) && $data['name'] == '') {
                return new JsonResponse(['error' => 'Name not provided'], Response::HTTP_BAD_REQUEST);
            } else {
                $order->setName($data['name']);
            }
            $order->setDescription($data['description'] ?? null);

            if (isset($data['order_date']) && $data['order_date'] != '') {
                $isValid = $this->checkValidDate($data['order_date']);

                if ($isValid) {
                    $order->setOrderDate(new DateTime($data['order_date']));
                } else {
                    return new JsonResponse(['error' => 'Invalid Date format'], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->em->persist($order);
            $this->em->flush();

            return new JsonResponse([
                'id' => $order->getId(),
                'name' => $order->getName() ? $order->getName() : '',
                'description' => $order->getDescription() ? $order->getDescription() : '',
                'order_date' => $order->getOrderDate() ?  $order->getOrderDate()->format('Y-m-d') : ''
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if (isset($data['name'])) {
                $order->setName($data['name']);
            }
            if (isset($data['description'])) {
                $order->setDescription($data['description']);
            }

            if (isset($data['order_date']) && $data['order_date']) {
                $isValid = $this->checkValidDate($data['order_date']);

                if ($isValid) {
                    $order->setOrderDate(new DateTime($data['order_date']));
                } else {
                    return new JsonResponse(['error' => 'Invalid Date format'], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->em->persist($order);
            $this->em->flush();

            return new JsonResponse([
                'id' => $order->getId(),
                'name' => $order->getName(),
                'description' => $order->getDescription(),
                'order_date' => $order->getOrderDate()->format('Y-m-d')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $order = $this->em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }
        /* dump($order->getId());
        die(); */
        try {
            $this->em->remove($order);
            $this->em->flush();

            return new JsonResponse([
                'message' => 'Order deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to remove order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /* UTILS */
    private function checkValidDate($date)
    {
        if (strtotime($date)) {
            return true;
        } else {
            return false;
        }
    }
}
