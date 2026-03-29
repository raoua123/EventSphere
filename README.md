#  EventSphere — Plateforme de réservation d'événements en Tunisie

EventSphere est une application web moderne conçue pour dynamiser l'écosystème événementiel en Tunisie. Des hackathons aux masterclasses, la plateforme simplifie la découverte et la réservation de places via une interface fluide et intuitive.

---

## Fonctionnalités Clés

### Pour les Participants
- **Découverte Intelligente** : Parcourez les événements avec photos, dates, lieux et compte à rebours des places.
- **Filtrage Dynamique** : Filtrez instantanément par disponibilité, par semaine ou via la barre de recherche.
- **Réservation Express** : Formulaire simplifié (Nom, Email, Téléphone) avec validation en temps réel.
- **Notifications** : Alertes interactives pour confirmer le succès ou signaler une erreur de réservation.

###  Pour les Administrateurs
- **Gestion Complète (CRUD)** : Interface dédiée pour ajouter, modifier ou supprimer des événements.
- **Tableau de Bord** : Statistiques sur le nombre total d'événements, les places restantes et la couverture géographique.
- **Email Sandboxing** : Intégration avec **Mailpit** pour intercepter tous les emails de test sans envoi réel.

---

## Technologies utilisées
- PHP 8.2 + Symfony 7
- LexikJWTAuthenticationBundle
- GesdinetJWTRefreshTokenBundle
- web-auth/symfony-bundle (WebAuthn/Passkeys)
- PostgreSQL 15
- Docker + Nginx
- JavaScript (Vanilla, WebAuthn API)

---

## Installation

### Prérequis
- Docker & Docker Compose installés
- Git

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/VOTRE_USER/MiniProjet2A-EventReservation-NomEquipe.git
cd MiniProjet2A-EventReservation-NomEquipe

# 2. Copier le fichier d'environnement
cp .env.example .env.local
# Remplir les valeurs (JWT_PASSPHRASE, DB_PASSWORD...)

# 3. Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
chmod 600 config/jwt/private.pem config/jwt/public.pem

# 4. Démarrer Docker
docker-compose up -d

# 5. Installer les dépendances PHP
docker exec symfony_php composer install

# 6. Créer la base de données et lancer les migrations
docker exec symfony_php php bin/console doctrine:database:create
docker exec symfony_php php bin/console doctrine:migrations:migrate

# 7. (Optionnel) Charger des données de test
docker exec symfony_php php bin/console doctrine:fixtures:load
```

L'application sera disponible sur **https://localhost**

---


## Accès Administrateur

```bash
docker exec symfony_php bin/console doctrine:query:sql "UPDATE \"user\" SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'votre-email@exemple.com'"
```

---

## Outils de Développement

Mailpit : Intercepte tous les emails envoyés (confirmations, alertes).
Interface Web : http://localhost:8025

---
## Tests Automatisés

Le projet utilise **PHPUnit** via le **Symfony PHPUnit Bridge**. En raison de l'utilisation de **PHP 8.4**, une procédure spécifique est nécessaire pour synchroniser la base de données de test sans erreurs de syntaxe.

### Préparation de la base de données de test
Exécutez ces commandes pour créer une base isolée (`_test`) et injecter le schéma manuellement :

```bash
# Créer la base de données de test
docker exec -it symfony_php php bin/console doctrine:database:create --env=test

# Générer le schéma SQL et l'injecter directement (évite les crashs de syntaxe PHP 8.4)
docker exec -it symfony_php php bin/console doctrine:schema:create --env=test --dump-sql > schema_setup.sql
docker exec -i symfony_db psql -U user -d event_reservation_test < schema_setup.sql
```

---


### Lancer les tests
Exécutez la suite de tests avec un affichage détaillé :

```bash
docker exec -it -e APP_ENV=test symfony_php vendor/bin/simple-phpunit --testdox
```

### Dépannage rapide


Si les styles ne s'affichent pas ou si la base de données refuse la connexion :

```bash
# Nettoyage complet des volumes et relance
docker-compose down -v
docker-compose up -d

# Forcer la compilation des assets
docker exec symfony_php npm run build

# Vider le cache Symfony
docker exec symfony_php bin/console cache:clear
```
