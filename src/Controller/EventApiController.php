<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events')]
class EventApiController extends AbstractController
{
    public function __construct(private EventRepository $eventRepo) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $events = $this->eventRepo->findUpcoming();
        return $this->json(array_map(fn(Event $e) => $e->toArray(), $events));
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepo->find($id);
        if (!$event) return $this->json(['error' => 'Événement introuvable'], 404);
        return $this->json($event->toArray());
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        $event = (new Event())
            ->setTitle($data['title'] ?? '')
            ->setDescription($data['description'] ?? '')
            ->setLocation($data['location'] ?? '')
            ->setSeats((int)($data['seats'] ?? 0))
            
->setImage($data['image'] ?? 'https://images.unsplash.com/random/200x200');
        try {
            $event->setDate(new \DateTimeImmutable($data['date'] ?? '+1 day'));
        } catch (\Exception) {
            return $this->json(['error' => 'Date invalide'], 400);
        }

        $errors = $validator->validate($event);
        if (count($errors) > 0) return $this->json(['errors' => 'Validation failed'], 400);

        $this->eventRepo->save($event, true);
        return $this->json($event->toArray(), 201);
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $event = $this->eventRepo->find($id);
        if (!$event) return $this->json(['error' => 'Introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) $event->setTitle($data['title']);
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['location'])) $event->setLocation($data['location']);
        if (isset($data['seats'])) $event->setSeats((int)$data['seats']);
       if (isset($data['image'])) $event->setImage($data['image']);
        
        if (isset($data['date'])) {
            try { $event->setDate(new \DateTimeImmutable($data['date'])); } 
            catch (\Exception) { return $this->json(['error' => 'Date invalide'], 400); }
        }

        $this->eventRepo->save($event, true);
        return $this->json($event->toArray());
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $event = $this->eventRepo->find($id);
        if (!$event) return $this->json(['error' => 'Introuvable'], 404);
        $this->eventRepo->remove($event, true);
        return $this->json(['success' => true]);
    }
}
