
# Foodtruck Symfony Project

## Description

Ce projet Symfony simule une application de Foodtruck où les utilisateurs peuvent commander des produits, laisser des avis. Ce projet utilise Symfony pour la logique serveur, MySQL pour la base de données, Twig pour les templates, et Tailwind CSS pour le design.

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- Node.js et npm
- Symfony CLI

### Étapes d'installation

1. Clonez le dépôt GitHub :

```bash
git clone https://github.com/x225franc/FoodtruckSymfony.git
cd FoodtruckSymfony
```

2. Installez les dépendances PHP avec Composer :

```bash
composer install
```

3. Installez les dépendances JavaScript avec npm :

```bash
npm install
```

4. mon fichier `.env` est fourni pour facilité les test (mauvaise pratique mais les informations contenu dedans ne sont pas importante) :


5. Créez la base de données et exécutez les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

6. Lancez le serveur Symfony :

```bash
symfony serve:start
```

7. Lancez le serveur Webpack Encore :

```bash
npm run watch
```

8. Chargez les fixtures pour peupler la base de données :

```bash
php bin/console doctrine:fixtures:load
```

### Utilisation

Accédez à l'application dans votre navigateur à l'adresse : [http://127.0.0.1:8000](http://127.0.0.1:8000)

Connectez-vous avec les comptes de test créés par les fixtures :

- **Admin** : admin@admin.fr / 123
- **Utilisateur** : user@user.fr / 123
- **Utilisateur banni** : banned@banned.fr / 123


PS : Vous pouvez vous crée un compte custom pour tester les envois de mails.

Testez !


# lien production (wip) : https://foodtrucksymfony.dealo.re