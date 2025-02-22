<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    private $em;
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }


    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $order = new Order();

        $order->setName($data['name'] ? $data['name'] : null);
        $order->setDescription($data['description'] ? $data['description'] : null);
        $order->setOrderDate(isset($data['order_date']) ? new DateTime($data['order_date']) : null);

        $this->em->persist($order);
        $this->em->flush();

        return new JsonResponse([
            'name' => $order->getName(),
            'description' => $order->getDescription(),
            'order_date' => $order->getOrderDate()->format('Y-m-d')
        ], 201);
    }

    /*  #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(Request $request, int $id): JsonResponse
    {
        $order = $this->em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['quantity'])) {
            $order->setQuantity($data['quantity']);
        }

        $entityManager->flush();

        return new JsonResponse($serializer->serialize($order, 'json'), 200, [], true);
    }
 */
    /* #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Order deleted successfully'], 204);
    } */
}
