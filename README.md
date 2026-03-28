# MiniProjet2A-EventReservation

Application Web de Gestion de Réservations d'Événements  
**Technologies:** Symfony 7 · JWT · Passkeys (WebAuthn) · PostgreSQL · Docker

---

## Membres de l'équipe
- Prénom NOM — FIA3-GL

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

## Structure des branches

| Branche | Rôle |
|---------|------|
| `main` | Code stable et fonctionnel |
| `dev` | Intégration et tests |
| `feature/auth` | Authentification JWT + Passkeys |
| `feature/events` | CRUD événements |
| `feature/reservations` | Gestion des réservations |

---

## API Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| POST | `/api/auth/register/options` | Options WebAuthn inscription | Public |
| POST | `/api/auth/register/verify` | Vérification inscription | Public |
| POST | `/api/auth/login/options` | Options WebAuthn connexion | Public |
| POST | `/api/auth/login/verify` | Vérification connexion → JWT | Public |
| POST | `/api/token/refresh` | Rafraîchir le JWT | Public |
| GET | `/api/auth/me` | Profil utilisateur | JWT |
| GET | `/api/events` | Liste des événements | Public |
| GET | `/api/events/{id}` | Détail événement | Public |
| POST | `/api/events` | Créer un événement | Admin |
| PUT | `/api/events/{id}` | Modifier un événement | Admin |
| DELETE | `/api/events/{id}` | Supprimer un événement | Admin |
| POST | `/api/reservations` | Créer une réservation | JWT |
| GET | `/api/reservations` | Mes réservations | JWT |
| GET | `/api/admin/reservations` | Toutes les réservations | Admin |
