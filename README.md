# Instructional Space Utilization Dashboard

This Laravel 11 application is designed to help predict and manage instructional space utilization based on enrollment projections.

## Features

- **Room and Seat Utilization Calculations:** Automatically calculate room and seat utilization percentages.
- **Enrollment Projections:** Input projected enrollment numbers and determine how many rooms will be needed.
- **Dynamic Dashboard:** Real-time updates based on different scenarios and data inputs.

## Getting Started

### Prerequisites

Ensure you have the following installed:

- [PHP 8.2+](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [Node.js & npm](https://nodejs.org/) (for frontend dependencies)
- [MySQL](https://www.mysql.com/) or any other database supported by Laravel

### Installation

1. **Clone the repository:**
    ```bash
    git clone https://github.com/yourusername/instructional-space-utilization.git
    cd instructional-space-utilization
    ```

2. **Install PHP dependencies:**
    ```bash
    composer install
    ```

3. **Install Node.js dependencies:**
    ```bash
    npm install
    ```

4. **Create and configure the `.env` file:**
    ```bash
    cp .env.example .env
    ```
   - Update database credentials and other necessary settings in the `.env` file.

5. **Generate the application key:**
    ```bash
    php artisan key:generate
    ```

6. **Run database migrations:**
    ```bash
    php artisan migrate
    ```

7. **Seed the database (if applicable):**
    ```bash
    php artisan db:seed
    ```

8. **Run the development server:**
    ```bash
    php artisan serve
    ```

### Frontend Compilation

To compile the frontend assets:

- **Development:**
    ```bash
    npm run dev
    ```

- **Production:**
    ```bash
    npm run build
    ```

### Testing

To run the test suite:

```bash
php artisan test
