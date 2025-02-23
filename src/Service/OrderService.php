<?php

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Order;
use App\Service\StockManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StockManager $stockManagerService
    ) {}


    public function createOrder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

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

        $this->stockManagerService->processOrderCreation($order);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function viewOrder(Request $request)
    {
        // Get filters from query parameters
        $name = $request->query->get('name');
        $description = $request->query->get('description');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Create the query builder for filtering orders
        $qb = $this->em->getRepository(Order::class)->createQueryBuilder('o');

        if ($name != '' && !is_null($name)) {
            $qb->andWhere('o.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($description != '' && !is_null($description)) {
            $qb->andWhere('o.description LIKE :description')
                ->setParameter('description', '%' . $description . '%');
        }

        if ($startDate) {
            $qb->andWhere('o.order_date >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('o.order_date <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }

        // Execute query
        $orders = $qb->getQuery()->getResult();

        return $orders;
    }

    public function updateOrder(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

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

        return $order;
    }

    public function deleteOrder($id)
    {
        $order = $this->em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        try {

            $this->stockManagerService->processOrderCancellation($order);
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
