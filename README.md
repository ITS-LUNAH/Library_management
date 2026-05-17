# 📚 Library Management System - AI Code Generation Simulation

## 📖 Description

Ce projet est une simulation de génération de code assistée par intelligence artificielle réalisée dans le cadre d’un mini-projet universitaire.

L’objectif principal est d’évaluer l’utilisation d’un outil IA de génération de code dans le développement d’une application web de gestion de bibliothèque basée sur l’architecture MVC.

Le projet a été développé avec :

- 🐘 PHP
- 🗄️ MySQL
- 🎨 HTML / CSS / JavaScript
- 🏗️ Architecture MVC

L’outil IA utilisé durant la simulation est **Claude 3.5 Sonnet**.

---

# ✨ Fonctionnalités

## 📚 Gestion des livres

- ➕ Ajouter un livre
- ✏️ Modifier un livre
- ❌ Supprimer un livre
- 📋 Afficher la liste des livres

## 👥 Gestion des membres

- ➕ Ajouter un membre
- ✏️ Modifier un membre
- ❌ Supprimer un membre
- 🔐 Gestion des mots de passe

## 🔄 Gestion des emprunts

- 📖 Emprunter un livre
- ↩️ Retourner un livre
- 📅 Gestion des dates d’emprunt et de retour

## 💰 Gestion des amendes

- ⚡ Calcul automatique des retards
- 🧾 Génération des amendes
- ✅ Mise à jour du statut des paiements

---

# 🗂️ Structure du projet

```bash
library-management/
│
├── config/
│   └── database.php
│
├── controllers/
│   ├── BookController.php
│   ├── BorrowController.php
│   ├── MemberController.php
│   └── FineController.php
│
├── database/
│   └── library.sql
│
├── models/
│   ├── Book.php
│   ├── Borrow.php
│   ├── Member.php
│   └── Fine.php
│
├── public/
│   ├── index.php
│   └── style.css
│
├── views/
│   ├── books/
│   ├── borrows/
│   ├── fines/
│   └── members/
│
└── README.md
```

---

# ⚙️ Installation

## 1️⃣ Cloner le projet

```bash
git clone https://github.com/USERNAME/REPOSITORY.git
```

## 2️⃣ Déplacer le projet dans le dossier serveur

Exemple avec XAMPP :

```bash
htdocs/library-management
```

## 3️⃣ Créer la base de données

- 🌐 Ouvrir phpMyAdmin
- 🗄️ Créer une base nommée :

```sql
library_db
```

- 📥 Importer le fichier :

```bash
database/library.sql
```

## 4️⃣ Configurer la connexion

Modifier le fichier :

```bash
config/database.php
```

Configurer :
- 👤 nom d’utilisateur MySQL
- 🔑 mot de passe
- 🗄️ nom de la base

## 5️⃣ Lancer le projet

Démarrer :
- ▶️ Apache
- ▶️ MySQL

Puis ouvrir :

```bash
http://localhost/library-management/public
```

---

# 🎯 Objectif pédagogique

Ce projet a pour objectif :

- 🤖 Étudier les capacités des outils IA de génération de code
- 📈 Évaluer leur impact sur la productivité
- 🧠 Analyser les avantages et limites de l’intelligence artificielle dans le développement logiciel

---

# 📊 Résultats de la simulation

La simulation a montré que l’IA permet :

- ⚡ Une génération rapide du code
- ⏱️ Un gain important de temps
- 🏗️ Une bonne structuration MVC
- 🔄 L’automatisation des tâches répétitives

Cependant :

- ⚠️ Certaines erreurs logiques nécessitent une correction manuelle
- 👨‍💻 La validation humaine reste indispensable

---

# 👩‍💻 Auteur(e)s

- 👩 Basma Khalil
- 👩 Fatima Zahra Zligui

🎓 Master Ingénierie des Systèmes d’Information  
🏫 Université Cadi Ayyad – FSSM

---

# 👩‍🏫 Encadrante

👩‍🏫 Mme EL ALAOUI HASNA

---

# 📜 Licence

📚 Projet académique – Usage pédagogique uniquement.
