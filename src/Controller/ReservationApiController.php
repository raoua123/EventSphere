<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/reservations')]
class ReservationApiController extends AbstractController
{
    public function __construct(
        private ReservationRepository $reservationRepo,
        private EventRepository $eventRepo
    ) {}

    // POST /api/reservations — create a reservation
    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, MailerInterface $mailer): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data    = json_decode($request->getContent(), true);
        $eventId = $data['event_id'] ?? null;
        $event   = $eventId ? $this->eventRepo->find($eventId) : null;

        if (!$event) {
            return $this->json(['error' => 'Événement introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($event->getAvailableSeats() <= 0) {
            return $this->json(['error' => 'Plus de places disponibles'], Response::HTTP_CONFLICT);
        }

        $reservation = (new Reservation())
            ->setEvent($event)
            ->setUser($this->getUser())
            ->setName($data['name'] ?? '')
            ->setEmail($data['email'] ?? '')
            ->setPhone($data['phone'] ?? '');

        $errors = $validator->validate($reservation);
        if (count($errors) > 0) {
            $msgs = [];
            foreach ($errors as $e) { $msgs[$e->getPropertyPath()] = $e->getMessage(); }
            return $this->json(['errors' => $msgs], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->reservationRepo->save($reservation);

        // Send confirmation email to Mailpit
        try {
            $email = (new Email())
                ->from('noreply@eventsphere.tn')
                ->to($reservation->getEmail())
                ->subject('Confirmation — ' . $event->getTitle())
                ->html($this->buildConfirmationEmail(
                    $reservation->getName(),
                    $event->getTitle(),
                    $event->getDate()->format('d/m/Y à H:i'),
                    $event->getLocation(),
                    $event->getAvailableSeats()
                ));
            $mailer->send($email);
        } catch (\Exception $e) {
            // Don't fail reservation if mail fails
        }

        return $this->json([
            'success'     => true,
            'message'     => 'Réservation confirmée !',
            'reservation' => $reservation->toArray(),
        ], Response::HTTP_CREATED);
    }

    // GET /api/reservations — current user's reservations
    #[Route('', methods: ['GET'])]
    public function myReservations(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reservations = $this->reservationRepo->findByUser($this->getUser());

        return $this->json(array_map(fn(Reservation $r) => [
            'id'        => $r->getId(),
            'name'      => $r->getName(),
            'email'     => $r->getEmail(),
            'phone'     => $r->getPhone(),
            'createdAt' => $r->getCreatedAt()->format('d/m/Y'),
            'event'     => [
                'id'       => $r->getEvent()->getId(),
                'title'    => $r->getEvent()->getTitle(),
                'date'     => $r->getEvent()->getDate()->format('d/m/Y à H:i'),
                'location' => $r->getEvent()->getLocation(),
                'image'    => $r->getEvent()->getImage(),
            ],
        ], $reservations));
    }

    // DELETE /api/reservations/{id} — cancel a reservation
    #[Route('/{id}', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reservation = $this->reservationRepo->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Réservation introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Only owner can cancel
        if ($reservation->getUser()?->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $em = $this->reservationRepo->getEntityManager();
        $em->remove($reservation);
        $em->flush();

        return $this->json(['success' => true]);
    }

    // GET /api/reservations/admin — all reservations (admin only)
    #[Route('/admin', methods: ['GET'])]
    public function adminAll(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservations = $this->reservationRepo->findAll();
        return $this->json(array_map(fn(Reservation $r) => $r->toArray(), $reservations));
    }

    private function buildConfirmationEmail(string $name, string $title, string $date, string $location, int $remaining): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="font-family:'Segoe UI',sans-serif;background:#09090f;color:#e8e6ff;margin:0;padding:40px 20px;">
          <div style="max-width:540px;margin:0 auto;background:#111120;border-radius:20px;overflow:hidden;border:1px solid rgba(124,109,250,0.2);">
            <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:36px;text-align:center;">
              <h1 style="margin:0;font-size:26px;font-weight:800;color:white;">EventSphere</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">Confirmation de réservation</p>
            </div>
            <div style="padding:36px 32px;">
              <p style="font-size:16px;margin:0 0 8px;">Bonjour <strong style="color:#a78bfa;">{$name}</strong>,</p>
              <p style="color:#8b87b0;font-size:14px;line-height:1.6;margin:0 0 28px;">Votre réservation a été confirmée avec succès.</p>
              <div style="background:rgba(124,109,250,0.08);border:1px solid rgba(124,109,250,0.2);border-radius:14px;padding:20px 24px;margin-bottom:24px;">
                <p style="font-size:18px;font-weight:700;margin:0 0 16px;color:#f1f5f9;">{$title}</p>
                <table style="width:100%;border-collapse:collapse;">
                  <tr><td style="padding:5px 0;color:#8b87b0;font-size:13px;width:70px;">Date</td><td style="padding:5px 0;font-size:13px;">{$date}</td></tr>
                  <tr><td style="padding:5px 0;color:#8b87b0;font-size:13px;">Lieu</td><td style="padding:5px 0;font-size:13px;">{$location}</td></tr>
                  <tr><td style="padding:5px 0;color:#8b87b0;font-size:13px;">Places</td><td style="padding:5px 0;font-size:13px;color:#4ade80;">{$remaining} places encore disponibles</td></tr>
                </table>
              </div>
              <p style="color:#475569;font-size:12px;">Conservez cet email comme preuve de réservation.</p>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}