# uSocial - Laravel Backend

The **uSocial** backend is a robust and scalable API built using **Laravel**. It provides the foundation for user authentication, influencer management, gem transactions, and real-time messaging features. Designed with a modular and maintainable architecture, the backend integrates seamlessly with the uSocial mobile and web applications.

## Features

-   **User Authentication**: Secured stateless API authentication with Laravel Sanctum.
-   **Role-Based Access Control**: Managed with Spatie's Laravel Permission package.
-   **Gem Transactions**: Purchase and manage gems via Stripe.
-   **Messaging System**: Integrated with CometChat for real-time communication.
-   **Wallet Management**: Store and utilize gems for messaging.
-   **Caching**: Leveraging Redis for improved performance. _(Included but not used)_

## Technology Stack

-   **Laravel**: Backend framework.
-   **PHP 8.x**: Latest version of PHP for optimal performance.
-   **MySQL**: Relational database.
-   **Redis**: Caching layer for improved performance.
-   **Stripe API**: For payment processing.
-   **CometChat API**: For real-time messaging.
-   **Docker**: Containerized environment using Laravel Sail.

## Prerequisites

Ensure the following are installed:

-   [Docker](https://www.docker.com/) (for Laravel Sail).
-   [Composer](https://getcomposer.org/) (for managing PHP dependencies).

## Installation

1. **Clone the Repository**:

    ```bash
    git clone https://github.com/emre88tosun/usocial-backend.git
    cd usocial-backend
    ```

2. **Set Up Environment**:

    Copy the `.env.example` file and configure it as `.env`:

    ```bash
    cp .env.example .env
    ```

3. **Install Dependencies**:

    ```bash
    composer install
    ```

4. **Run Migrations**:

    ```bash
    php artisan migrate
    ```

5. **Seed the Database** (mandatory):

    ```bash
    php artisan db:seed
    ```

6. **Start the Application**:

    Using Laravel Sail:

    ```bash
    ./vendor/bin/sail up -d
    ```

    Alternatively, run locally:

    ```bash
    php artisan serve
    ```

## API Documentation

API documentation is available at `/api/documentation`
