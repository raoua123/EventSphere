<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $events = [
            [
                'title'       => 'Forum International de l\'Intelligence Artificielle',
                'description' => 'Deux jours dédiés aux dernières avancées en IA générative, machine learning et éthique algorithmique. Intervenants internationaux, ateliers pratiques et networking intensif.',
                'date'        => '+15 days',
                'location'    => 'Centre des Congrès de Tunis, Lac 2',
                'seats'       => 300,
                'image'       => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&q=80',
            ],
            [
                'title'       => 'Hackathon Green Tech 2025',
                'description' => '48 heures pour concevoir des solutions technologiques durables. Open aux développeurs, designers et entrepreneurs. Prix en espèces et incubation pour les meilleurs projets.',
                'date'        => '+22 days',
                'location'    => 'ESPRIT School of Engineering, Ariana',
                'seats'       => 150,
                'image'       => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&q=80',
            ],
            [
                'title'       => 'TEDx Tunis — Redéfinir l\'Avenir',
                'description' => 'Une soirée d\'idées qui méritent d\'être propagées. Dix speakers inspirants issus de l\'art, la science et l\'entrepreneuriat pour challenger nos perceptions.',
                'date'        => '+30 days',
                'location'    => 'Théâtre de l\'Opéra de Tunis',
                'seats'       => 500,
                'image'       => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80',
            ],
            [
                'title'       => 'Masterclass Cybersécurité — Ethical Hacking',
                'description' => 'Formation intensive d\'une journée avec des experts certifiés CEH. Prise en main des outils de pentesting, analyse de vulnérabilités et défense des systèmes.',
                'date'        => '+8 days',
                'location'    => 'Hôtel The Residence Tunis, La Marsa',
                'seats'       => 80,
                'image'       => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80',
            ],
            [
                'title'       => 'Startup Weekend Sfax',
                'description' => 'Du vendredi soir au dimanche. 54 heures pour passer de l\'idée à la startup. Mentors, investisseurs et partenaires présents pour accompagner les équipes.',
                'date'        => '+45 days',
                'location'    => 'Technopark de Sfax',
                'seats'       => 120,
                'image'       => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800&q=80',
            ],
            [
                'title'       => 'Conférence UX/UI Design — Tendances 2025',
                'description' => 'Une journée pour explorer les nouvelles frontières du design d\'expérience. Figma AI, design systems à grande échelle, accessibilité et recherche utilisateur avancée.',
                'date'        => '+12 days',
                'location'    => 'Google Developer Space, Les Berges du Lac',
                'seats'       => 200,
                'image'       => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80',
            ],
            [
                'title'       => 'DevFest Tunis 2025',
                'description' => 'La plus grande conférence développeurs de Tunisie. Tracks Android, Web, Cloud et IA. 40+ sessions techniques, codelabs et rencontres avec des GDE Google.',
                'date'        => '+60 days',
                'location'    => 'Palais des Congrès de Tunis',
                'seats'       => 1000,
                'image'       => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800&q=80',
            ],
            [
                'title'       => 'Atelier Blockchain & Web3 pour Débutants',
                'description' => 'Comprendre la blockchain sans jargon technique. Smart contracts, DeFi, NFTs et cas d\'usage concrets. Atelier hands-on avec MetaMask et Solidity.',
                'date'        => '+5 days',
                'location'    => 'Impact Hub Tunis, El Manar',
                'seats'       => 40,
                'image'       => 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800&q=80',
            ],
        ];

        foreach ($events as $data) {
            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description']);
            $event->setDate(new \DateTimeImmutable($data['date']));
            $event->setLocation($data['location']);
            $event->setSeats($data['seats']);
            $event->setImage($data['image']);
            $manager->persist($event);
        }

        $manager->flush();
    }
}
