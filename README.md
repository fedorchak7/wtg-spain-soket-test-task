# Real-Time Messaging App

A real-time messaging application built with Laravel 12, React 18, Inertia.js, and Laravel Reverb WebSockets.

## Stack

- **Backend:** PHP 8.3, Laravel 12
- **Frontend:** React 18, Inertia.js v2, Tailwind CSS v3
- **Real-time:** Laravel Reverb (WebSockets), Laravel Echo
- **Queue/Cache:** Redis
- **Database:** MySQL 8
- **Infrastructure:** Laravel Sail (Docker)

## Features

### Backend
- User registration and authentication (Laravel Breeze)
- Private WebSocket channels per user (`messages.{id}`)
- Presence channel for online user tracking (`online`)
- Messages stored in MySQL with sender, receiver, text, timestamps, and `read_at`
- Unread message counts grouped by sender
- Broadcasting via `MessageSent` event dispatched through Redis queue
- Pagination for message history (offset-based, 25 messages per page)
- Form Request validation with self-message protection
- Service layer (`MessageService`) with DTO (`PaginatedMessages`)
- Composite DB indexes for optimized conversation and unread queries
- Feature tests with Pest 3

### Frontend
- User list with online indicators and unread count badges
- Real-time message delivery without page reload
- Missed messages shown on next login
- Load more (pagination) on scroll
- Auto-scroll to latest message
- Responsive layout (mobile-friendly)

## Setup (Docker / Laravel Sail)

### Requirements
- Docker & Docker Compose

### Installation

```bash
# 1. Clone the repository
git clone <repo-url>
cd <project-folder>

# 2. Copy environment file
cp .env.example .env

# 3. Fill in REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET in .env with any random strings

# 4. Install PHP dependencies
# If PHP is installed locally:
composer install
# Otherwise, using Sail's Docker image:
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs

# 5. Start containers
./vendor/bin/sail up -d

# 6. Generate app key
./vendor/bin/sail artisan key:generate

# 7. Run migrations
./vendor/bin/sail artisan migrate

# 8. Build frontend assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 9. (Optional) Seed demo users
./vendor/bin/sail artisan db:seed
```

### Demo Users

After seeding, the following accounts are available (password: `password`):

| Name    | Email               |
|---------|---------------------|
| Alice   | alice@example.com   |
| Bob     | bob@example.com     |
| Charlie | charlie@example.com |
| Diana   | diana@example.com   |
| Eve     | eve@example.com     |

### Access

| Service     | URL                   |
|-------------|-----------------------|
| Application | http://localhost      |
| Reverb WS   | ws://localhost:8080   |
| MySQL       | localhost:3306        |
| Redis       | localhost:6379        |

### Useful Commands

```bash
# Start all services
./vendor/bin/sail up -d

# Stop all services
./vendor/bin/sail down

# Run tests
./vendor/bin/sail artisan test --compact

# Tail logs
./vendor/bin/sail artisan pail
```

## Architecture

```
app/
├── Events/MessageSent.php          # Broadcast event (private channel)
├── Http/
│   ├── Controllers/
│   │   ├── MessageController.php   # GET /messages/{user}, POST /messages/{user}
│   │   └── UserController.php      # GET /dashboard
│   ├── Requests/SendMessageRequest.php
│   └── Resources/
│       ├── MessageResource.php
│       └── UserResource.php
├── Models/
│   ├── Message.php                 # sender_id, receiver_id, text, read_at
│   └── User.php
└── Services/
    ├── MessageService.php          # send, conversation, markAsRead, unreadCounts
    └── DTOs/PaginatedMessages.php

resources/js/
├── Pages/Dashboard.jsx
├── Components/
│   ├── ChatBox.jsx
│   ├── UserList.jsx
│   └── MessageBubble.jsx
└── hooks/
    ├── useChat.js                  # messages state, Echo listener
    └── useOnlineUsers.js           # presence channel

routes/
├── web.php
└── channels.php                    # messages.{id} private, online presence
```
