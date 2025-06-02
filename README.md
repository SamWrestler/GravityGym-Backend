
# GravityGym Backend – Laravel API


A robust RESTful backend built with Laravel, handling authentication, user and class management, enrollments, subscriptions, and payment integration. Designed for scalability and secure API interaction with the frontend.




## Features

* Laravel 12 RESTful API structure with file-based routing and controllers
* Sanctum-powered authentication: login, registration, password reset, email verification
* Eloquent models & relationships for users, instructors, classes, enrollments, subscriptions
* Resource controllers with form request validation for CRUD operations
* Migration & seeder setup for database schema (users, roles, classes, schedules, payments)
* Enrollment & subscription management endpoints backed by dedicated service classes
* Payment integration via HTTP client to external gateways (initialize and verify transactions)
* Middleware for role-based access control (admin vs. instructor vs. member)
* API resources & transformers ensuring consistent JSON responses
* Job queue & notifications (email/SMS) for enrollment confirmations and payment updates
* Exception handling & custom API error formatting
* Rate limiting & CSRF protection for secure API access
* Logging and audit trails for critical events (user actions, payment status changes)
* Environment variable support (`.env`) for database, mail, and payment credentials
* PHPUnit test setup for unit and feature tests of key endpoints
* Dockerfile & Docker Compose configuration for local development and containerization
* API documentation via OpenAPI/Swagger annotations or Postman collection
* Git CI workflows for linting, testing, and deployment automation
* Role/permission management using policies and gates for fine-grained authorization
* Service providers and event listeners for modular business logic and extensibility


## Tech Stack


* **Framework & Language:** Laravel 12 (PHP 8.x)
* **Authentication & Security:** Laravel Sanctum (SPA token/cookie), CSRF protection, rate limiting
* **Database & ORM:** Eloquent ORM (MySQL or PostgreSQL), Migrations, Seeders, Factories
* **API Structure:** RESTful routes (API Resource Controllers), Form Requests for validation
* **Authorization:** Policies & Gates for role-based access (admin, instructor, member)
* **Payment Integration:** Guzzle HTTP Client or Laravel HTTP for gateway initialization & verification
* **Queues & Jobs:** Laravel Queues (Redis/Database driver) for emails, notifications, OTP cleanup
* **Notifications & Mail:** Laravel Notifications (Mail, SMS via Nexmo/Twilio, etc.), Mailable classes
* **Testing:** PHPUnit (Feature & Unit tests), API test suites
* **Environment & Config:** `.env` (database credentials, mail settings, payment keys)
* **Containerization:** Docker & Docker Compose (PHP-FPM, Nginx, MySQL, Redis)
* **Logging & Monitoring:** Laravel Log channels (single, daily), audit trails via model events
* **Documentation:** OpenAPI/Swagger (annotations) or Postman collection for API endpoints
* **CI/CD & Tooling:** GitHub Actions (linting, testing, deployment), Composer for package management
## How it works


GravityGym’s architecture is split into two tightly integrated parts: a Laravel 12 RESTful API backend that handles all data, business logic, and security, and a Next.js 15 frontend that delivers a responsive, interactive user experience. Authentication, data fetching, and role-based access are coordinated between both layers via Sanctum and REST endpoints. The following sections describe each phase in detail and illustrate how the frontend and backend interact throughout typical user and admin workflows.

---

### 1. Initialization & Configuration

1. **Environment Variables**

   * Backend: Reads values from `.env` (APP\_URL, database credentials, mail settings, Sanctum settings, payment gateway keys).
   * Frontend: Loads `.env.local` (`NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_MAPBOX_TOKEN`, any other API URLs) for build and runtime.

2. **Backend Bootstrapping**

   * `artisan migrate --seed` creates and populates tables: users, roles, instructors, classes, enrollments, subscriptions, payments, etc.
   * Laravel automatically registers service providers (e.g., SanctumServiceProvider) and routes defined under `routes/api.php`.
   * Middleware for CSRF (`EnsureFrontendRequestsAreStateful`), authentication (`auth:sanctum`), and role-gate checks (`can:isAdmin`, `can:isInstructor`) are applied.

3. **Frontend Bootstrapping**

   * Next.js reads configuration in `next.config.js` and initializes the App Router.
   * Tailwind CSS compiles utility classes from `tailwind.config.js` via PostCSS.
   * A centralized Axios instance (`src/lib/axios.js`) is created, pointing to `NEXT_PUBLIC_API_URL` with `withCredentials: true` to include Sanctum cookies.
   * SWR’s global configuration is set in a `_app.jsx` or root layout to revalidate on focus and handle errors.
   * Custom hooks (`useAuth`, `useClickOutside`, `useWindowSize`, `NavigateToTop`) are loaded for use across all components.

4. **Docker & Development Setup**

   * Backend: Docker Compose defines containers for PHP-FPM, Nginx, MySQL (or PostgreSQL), and Redis (for queues). Running `docker-compose up -d` spins up the API environment.
   * Frontend: `npm install && npm run dev` launches Next.js on `localhost:3000`, automatically picking up `.env.local`.

---

### 2. Authentication & Session Management

1. **CSRF Token Initialization**

   * Before any protected API call (login, register, fetching current user), the frontend calls `GET /sanctum/csrf-cookie` on Laravel.
   * Laravel returns a secure `XSRF-TOKEN` cookie; Axios automatically attaches this token in the `X-XSRF-TOKEN` header for subsequent POST/PUT/DELETE requests.

2. **User Registration & Login**

   * **Registration Flow**

     * User navigates to `/register` (Next.js page). The form collects name, email, password, and confirmation.
     * On submission, Axios sends `POST /api/register` with form data. Laravel’s `RegisterController` validates via a `RegisterRequest`, creates a new `User` record (with default “member” role), and issues an HTTP-only session cookie.
     * Frontend receives a successful response, SWR’s `useAuth` hook calls `GET /api/user` to fetch the newly created user and caches it. The user is then redirected to `/classes` or `/dashboard` depending on their role.

   * **Login Flow**

     * User navigates to `/login`. The form asks for email and password.
     * On submission, Axios sends `POST /api/login`. Laravel’s `LoginController` validates credentials, issues a Sanctum session cookie, and returns the authenticated user’s JSON.
     * SWR revalidates `GET /api/user` and caches the user data. The Next.js router pushes the user to `/classes` (for members) or `/dashboard` (for admins/instructors).

3. **Fetching the Authenticated User**

   * The `useAuth` hook calls `useSWR("/api/user", fetcher)`. Laravel’s `GET /api/user` endpoint returns user data, including `id`, `name`, `email`, `role`, and any profile settings.
   * SWR caches this response; if the user visits a protected page and `useAuth` returns `undefined`, the frontend redirects to `/login`.

4. **Logout**

   * Clicking “Logout” in the layout invokes `POST /api/logout`. Laravel invalidates the session, and the frontend calls `mutate("/api/user", null)` to clear the SWR cache. Next.js then redirects the user to `/login`.

5. **Middleware & Route Protection**

   * Frontend uses Next.js middleware (or checks inside `getServerSideProps`) to guard routes: if `useAuth` returns no user, redirect to `/login`.
   * Guest-only pages (`/login`, `/register`) check if `useAuth` returns a user; if so, redirect to `/classes` or `/dashboard`.

---

### 3. Public Member Workflow (Classes, Enrollment, Profile)

1. **Browsing Classes**

   * **Homepage (`/classes`)**

     * Upon visiting `/classes`, SWR fetches `GET /api/classes?filters…` (e.g., `?instructor_id=2`, `?date=2025-06-15`).
     * Laravel’s `ClassController@index` applies Eloquent queries—filtering by date, instructor, or category—and returns paginated results: an array of class objects (id, title, description excerpt, schedule, price, capacity, instructor name).
     * Next.js renders a responsive grid of `<ClassCard />` components, each showing: title, date/time, instructor name, price, and an “Enroll” button (disabled if not authenticated or if capacity is reached).
     * The search bar in the header calls `GET /api/classes?search={query}`; as the user types, previous search history in `localStorage` is displayed. Pressing Enter triggers SWR revalidation with the `search` query parameter.

2. **Viewing Class Details**

   * **Detail Page (`/classes/[id]`)**

     * When clicking a class card, Next.js navigates to `/classes/[id]`. SWR calls `GET /api/classes/{id}`.
     * Laravel’s `ClassController@show` retrieves the class model with relationships: instructor, enrolled count, remaining seats.
     * Next.js renders full details: title, description, image carousel (if images exist), instructor bio, schedule, capacity, and price. An “Enroll Now” button triggers enrollment.

3. **Enrollment & Payment Flow**

   * **Enrollment Request**

     * Clicking “Enroll Now” calls `POST /api/enrollments` with `{ class_id }`. Laravel’s `EnrollmentController@store` checks:

       * Is the user authenticated?
       * Does the class have available seats?
     * If capacity is available and the class is free, Laravel creates an `Enrollment` record (status “confirmed”) and returns JSON `{ enrollment_id, status: "confirmed" }`. If payment is required (e.g., paid class), Laravel instead returns `{ enrollment_id, payment_required: true, payment_url }`.
   * **Payment Processing**

     * If `payment_required: true`, Next.js immediately redirects the browser to `payment_url`. This might be a route like `/api/payments/initiate` that returns a redirection to a third-party gateway (Stripe, PayPal).
     * After completing payment on the gateway side, the provider calls a Laravel webhook endpoint (e.g., `POST /api/payments/verify`) with a signature and transaction details. Laravel’s `PaymentController@verify` validates the signature, updates the `Enrollment` record to `status: "paid"`, and triggers an email notification.
     * Upon returning from the gateway, Next.js lands on a confirmation page (`/classes/[id]/confirmation`) which calls `GET /api/enrollments/{enrollment_id}` to verify `status: "paid"` and displays a “Enrollment Confirmed” message with receipt details.

4. **My Enrollments & Profile Management**

   * **Viewing Enrollments (`/profile/enrollments`)**

     * SWR fetches `GET /api/enrollments?user_id={user.id}`. Laravel’s `EnrollmentController@index` returns all enrollment records for the user, each including: class title, schedule, price, status (“confirmed”, “cancelled”, “paid”), and timestamps.
     * Next.js maps over these records in `<EnrollmentCard />` components showing status badges and a “Cancel” button if within the allowed cancellation window.
   * **Canceling an Enrollment**

     * Clicking “Cancel” calls `DELETE /api/enrollments/{id}`. Laravel’s `EnrollmentController@destroy` checks if the enrollment can be cancelled (e.g., >24 hours before class). If allowed, it deletes or marks the record “cancelled” and issues a refund if paid. The frontend calls `mutate("/api/enrollments?user_id={id}")` to revalidate the list.
   * **Editing Profile (`/profile`)**

     * This page calls `GET /api/user` via `useAuth` and populates a form with name, email, and phone number.
     * On submission, the form calls `PUT /api/user` with updated fields. Laravel’s `UserController@update` validates via `UpdateUserRequest` and updates the user. SWR’s `mutate("/api/user")` refreshes the cached user data.
   * **Password Change**

     * Under `/profile/password`, the form collects `current_password`, `new_password`, and `new_password_confirmation`. Frontend calls `POST /api/password/change`. Laravel’s `PasswordController@change` validates credentials and, if successful, hashes and updates the password. A confirmation email is sent via a queued `PasswordChanged` notification.

---

### 4. Instructor & Admin Workflow

1. **Role-Based Routing & Layouts**

   * After login, `useAuth` returns a user object with `role` (e.g., `"member"`, `"instructor"`, `"admin"`).
   * Next.js uses nested layouts:

     * Public pages (e.g., `/classes`, `/classes/[id]`) are accessible to everyone.
     * `/dashboard` is wrapped with an `AuthLayout`: it checks `user.role`—if `"member"`, redirect to `/classes`; if `"instructor"`, show the InstructorSidebar; if `"admin"`, show the AdminSidebar.

2. **Instructor Panel (`/dashboard/instructor`)**

   * **Viewing My Classes**

     * SWR calls `GET /api/instructor/{id}/classes`. Laravel’s `InstructorController@classes` returns all classes taught by that instructor.
     * Next.js renders a table of classes with columns: title, date/time, capacity, enrolled count, and a “View Enrollments” button.
   * **Managing Enrollments**

     * Clicking “View Enrollments” sends `GET /api/classes/{class_id}/enrollments`. Each enrollment includes user details and status.
     * The instructor can mark an enrollment “Attended” or “No-Show” by calling `PUT /api/enrollments/{enrollment_id}` with `{ status: "attended" }` or `{ status: "no-show" }`. Laravel updates the record and optionally sends a notification. SWR revalidates `/api/classes/{id}/enrollments`.
   * **Creating & Updating Classes**

     * Under `/dashboard/instructor/classes/new`, the form collects title, description, date/time, price, and capacity. On submission, it calls `POST /api/admin/classes` (instructors share the same endpoint as admins for class creation). Laravel’s `ClassController@store` validates via `ClassRequest` and associates the new class with the instructor. SWR revalidates `/api/instructor/{id}/classes`.
     * Editing an existing class at `/dashboard/instructor/classes/edit/[id]` calls `PUT /api/admin/classes/{id}` with updated data. SWR revalidates the instructor’s classes and the class detail cache.

3. **Admin Dashboard (`/dashboard/admin`)**

   * **User Management (`/dashboard/users`)**

     * SWR calls `GET /api/admin/users`. Laravel’s `Admin\UserController@index` returns all users with pagination.
     * Admin can “Edit” a user by opening a modal form that calls `PUT /api/admin/users/{id}`. Fields include name, email, role, and status (active/inactive).
     * “Delete” operations call `DELETE /api/admin/users/{id}`. Laravel removes the user and related enrollments; SWR revalidates the users list.
   * **Class & Instructor Management**

     * `/dashboard/classes` calls `GET /api/admin/classes` to list every class. Admin can edit or delete any class via `PUT /api/admin/classes/{id}` or `DELETE /api/admin/classes/{id}`.
     * `/dashboard/instructors` calls `GET /api/admin/instructors` to list instructor profiles (name, email, bio). “Edit” calls `PUT /api/admin/instructors/{id}`; “Delete” calls `DELETE /api/admin/instructors/{id}`.
   * **Enrollment & Subscription Oversight**

     * `/dashboard/enrollments` calls `GET /api/admin/enrollments`. Returns all enrollment records for all classes, each linked with user and class.
     * Admin can manually change an enrollment’s status (e.g., from “pending” to “confirmed”) by calling `PUT /api/admin/enrollments/{id}` with `{ status: "confirmed" }`. Laravel triggers a notification to the member. SWR revalidates the enrollments endpoint.
   * **Reporting & Analytics**

     * `/dashboard/reports` calls endpoints like `GET /api/admin/reports/attendance` and `GET /api/admin/reports/revenue`.
     * Laravel’s `ReportController` aggregates data from enrollments and payments (using Eloquent’s `sum()`, `count()`, and `groupBy()`), returning JSON objects: daily attendance counts, total revenue per class, and month-over-month trends.
     * Next.js uses a chart library (e.g., Recharts) inside `<AttendanceChart />` and `<RevenueChart />` components to visualize these datasets dynamically.

---

### 5. Data Flow & State Synchronization

1. **Centralized Axios & CSRF**

   * The Axios instance (`src/lib/axios.js`) is configured with:

     ```js
     const axiosInstance = axios.create({
       baseURL: process.env.NEXT_PUBLIC_API_URL,
       withCredentials: true, // includes Sanctum cookie
       headers: { "Content-Type": "application/json" },
     });
     ```
   * All SWR fetchers and manual calls use `axiosInstance` so CSRF tokens and cookies are consistently applied.

2. **SWR Caching & Revalidation**

   * Every data-driven component uses `useSWR(key, fetcher)`, where `key` is the API endpoint (e.g., `"/api/classes"`, `"/api/user"`).
   * After a mutation (e.g., `POST /api/enrollments`), the code calls `mutate("/api/enrollments?user_id={id}")` to force an immediate re-fetch and keep the UI in sync.
   * SWR automatically revalidates data when the window regains focus or the network reconnects, ensuring stale data doesn’t persist.

3. **Error Handling**

   * Frontend: Each SWR hook returns `[data, error]`. While `data` is undefined and `error` is undefined, a `<Spinner />` or skeleton UI is shown. If `error` is truthy, a toast (e.g., via React Toastify) displays the error message.
   * Backend: Controllers wrap actions in `try/catch`. Validation failures trigger a 422 response with structured JSON:

     ```json
     {
       "message": "The given data was invalid.",
       "errors": {
         "title": ["Title is required."],
         "date": ["Date must be in the future."]
       }
     }
     ```
   * The frontend extracts `error.response.data.errors` and marks corresponding form fields with messages.

4. **Form Requests & Validation (Backend)**

   * Laravel’s `FormRequest` classes (e.g., `ClassRequest`, `EnrollmentRequest`, `UserRequest`) define `rules()` and `authorize()` methods.
   * On failed validation, Laravel returns a 422 with field-specific errors; the frontend displays these inline.
   * Authorization checks (e.g., `authorize()` returns `true` only if the authenticated user has the correct role or owns the resource) prevent unauthorized operations.

---

### 6. Notifications & Background Tasks

1. **Real-Time Notifications (Frontend)**

   * SWR polls `/api/notifications?user_id={id}` every 60 seconds. Laravel’s `NotificationController@index` returns unread notifications (e.g., “Your enrollment for Yoga Class has been confirmed”).
   * In the header, a bell icon shows a red badge with unread count. Clicking it opens a dropdown list of `<NotificationItem />`, each showing title, message, and timestamp. Clicking one calls `PUT /api/notifications/{id}/read`.

2. **Backend Queued Jobs & Email/SMS**

   * On certain triggers (e.g., successful payment, enrollment cancellation), Laravel dispatches queued jobs:

     * `SendEnrollmentConfirmation` (sends email to user with enrollment details).
     * `NotifyInstructorOfNewEnrollment` (emails the instructor that a member has enrolled).
   * Queues use Redis (configured in `.env` as `QUEUE_CONNECTION=redis`). A `php artisan queue:work` process runs in production to handle these jobs asynchronously.

3. **Scheduled Tasks (Scheduler)**

   * In `app/Console/Kernel.php`, scheduled commands run at set intervals:

     * **Monthly Reporting**: `php artisan report:generate` compiles attendance and revenue reports and stores them in `storage/reports/`.
     * **Subscription Expiration Check**: `php artisan subscription:expire-check` runs daily at midnight, finds subscriptions with `end_date < today`, marks them “expired,” and notifies users via email.
     * **Database Cleanup**: `php artisan db:cleanup` removes orphaned records (e.g., classes with no instructor, stale sessions).
   * These commands are registered using `$schedule->command(...)->dailyAt('00:00');`. Crontab on the server runs `php artisan schedule:run` every minute to trigger due tasks.

---

### 7. Deployment & CI/CD

1. **Backend Deployment**

   * **Docker Compose**

     * Services: `php-fpm` (Laravel), `nginx`, `mysql`, `redis`.
     * Volumes:

       * `app` volume mounts the Laravel code.
       * `db_data` persists MySQL data.
       * `redis_data` persists Redis data.
   * **Production Steps**

     1. Pull the latest code from the `main` branch.
     2. Run `composer install --optimize-autoloader --no-dev`.
     3. Copy `.env.example` to `.env`, set production credentials.
     4. Run `php artisan migrate --force` and `php artisan db:seed --force`.
     5. Build optimized cache: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
     6. Start queue worker: `php artisan queue:work --daemon`.
     7. Serve via Nginx pointing to `public/index.php`.

2. **Frontend Deployment**

   * **Vercel (or Netlify)**

     1. Connect GitHub repository.
     2. Set environment variables:

        * `NEXT_PUBLIC_API_URL=https://api.gravitygym.com`
        * `NEXT_PUBLIC_MAPBOX_TOKEN=pk.xxxxxx`
     3. Build command: `npm run build`; Output directory: `.next`.
     4. Vercel automatically runs `next build` and serves the optimized production build.
   * **Custom Node Server**

     1. On a Linux host, `ssh` into the server.
     2. Clone the repo and run `npm ci`.
     3. Set environment variables in `~/.env.production`.
     4. Run `npm run build` and `npm run start` behind a process manager (PM2 or systemd).
     5. Nginx reverse-proxies requests on port 80/443 to the Node.js process on port 3000.

3. **Continuous Integration**

   * A GitHub Actions workflow (`.github/workflows/ci.yml`) runs on each PR or push to `main`:

     * **Backend Job**:

       * Check out code.
       * Run `composer install --no-interaction`.
       * Run `php artisan test` to execute PHPUnit tests (unit and feature).
       * Lint PHP code (`phpcs` or `php-cs-fixer`).
     * **Frontend Job**:

       * Check out code.
       * Run `npm ci`.
       * Run `npm run lint` (ESLint).
       * Run `npm run test` (Jest or React Testing Library).
       * Build with `npm run build` to catch build-time errors.

---

### 8. Summary of End-to-End Interactions

1. **User Registration & Login**

   * **Frontend**: User submits `/register` form → Axios sends `POST /api/register`.
   * **Backend**: Laravel validates, creates user, issues session cookie → returns user JSON.
   * **Frontend**: SWR’s `useAuth` calls `GET /api/user`, caches user, redirects based on `role`.

2. **Browsing & Filtering Classes**

   * **Frontend**: Visiting `/classes?search=Yoga&date=2025-06-20` triggers SWR to fetch `GET /api/classes?search=Yoga&date=2025-06-20`.
   * **Backend**: `ClassController@index` queries Eloquent model:

     ```php
     ClassModel::where('title', 'like', '%Yoga%')
       ->whereDate('date', '2025-06-20')
       ->paginate(10);
     ```
   * **Frontend**: Renders `<ClassCard />` for each result.

3. **Enrollment & Payment**

   * **Frontend**: Click “Enroll” on a class → Axios `POST /api/enrollments` with `{ class_id }`.
   * **Backend**: `EnrollmentController@store`:

     * Validates class capacity.
     * If free: creates `Enrollment` with `status="confirmed"`, returns `{ status: "confirmed" }`.
     * If paid: creates a pending `Enrollment`, calls a `PaymentGateway` service to generate `payment_url`, returns `{ payment_required: true, payment_url }`.
   * **Frontend**: If `payment_required`, redirect browser to `payment_url`.
   * **Backend**: Payment gateway sends a webhook to `POST /api/payments/verify` → `PaymentController@verify` marks `Enrollment.status="paid"`, dispatches `SendConfirmationEmail` job.
   * **Frontend**: On return, SWR revalidates `GET /api/enrollments?user_id={id}`. User sees “Enrollment Confirmed.”

4. **Member Profile & Enrollment Management**

   * **Frontend**: Visiting `/profile/enrollments` calls SWR for `GET /api/enrollments?user_id={id}`.
   * **Backend**: Returns all enrollments ordered by date; each record includes class title, schedule, price, and `status`.
   * **Frontend**: Renders `<EnrollmentCard />` with “Cancel” button if allowed.
   * **Frontend**: Click “Cancel” → Axios `DELETE /api/enrollments/{id}`.
   * **Backend**: `EnrollmentController@destroy` validates cancellation window, marks `status="cancelled"`, issues refund (if paid), triggers `SendCancellationEmail`.
   * **Frontend**: Calls `mutate("/api/enrollments?user_id={id}")` to refresh the list.

5. **Instructor Panel**

   * **Frontend**: Instructor navigates to `/dashboard/instructor`. `useAuth` returns `user.role === "instructor"`, so the InstructorLayout appears.
   * **Frontend**: SWR fetches `GET /api/instructor/{id}/classes`.
   * **Backend**: `InstructorController@classes` returns classes where `instructor_id = {id}`.
   * **Frontend**: Renders a table of classes; clicking “View Enrollments” calls `GET /api/classes/{class_id}/enrollments`.
   * **Backend**: `EnrollmentController@forClass` returns user list and statuses.
   * **Frontend**: Instructor can click “Mark Attended” → `PUT /api/enrollments/{enrollment_id}`.
   * **Backend**: Updates `status="attended"` and dispatches a notification.

6. **Admin Dashboard**

   * **Frontend**: Admin goes to `/dashboard/admin`. `useAuth` returns `user.role === "admin"`, so AdminLayout appears.
   * **Frontend**: SWR fetches `GET /api/admin/users`, `GET /api/admin/classes`, `GET /api/admin/enrollments`.
   * **Backend**:

     * `Admin\UserController@index` returns users with pagination.
     * `Admin\ClassController@index` returns all classes.
     * `Admin\EnrollmentController@index` returns all enrollments.
   * **Frontend**: Admin can edit a user via `PUT /api/admin/users/{id}`. SWR revalidates `"/api/admin/users"`.
   * **Frontend**: Admin edits or deletes classes via `PUT /api/admin/classes/{id}`, `DELETE /api/admin/classes/{id}`. SWR revalidates `"/api/admin/classes"`.
   * **Frontend**: Changes enrollment status with `PUT /api/admin/enrollments/{id}`; SWR revalidates `"/api/admin/enrollments"`.

7. **Notifications & Real-Time Updates**

   * **Frontend**: SWR polls `GET /api/notifications?user_id={id}` every 60 seconds. The bell icon shows unread count.
   * **Backend**: `NotificationController@index` queries `notifications` table for unread notifications (`read_at` is null).
   * **Frontend**: Clicking a notification calls `PUT /api/notifications/{id}/read` to mark as read. SWR revalidates `"/api/notifications?user_id={id}"`.
   * **Backend**: Notifications are generated by events (e.g., `EnrollmentConfirmed`, `PaymentReceived`) using Laravel’s Notification system (Mail, SMS).

---

### 9. Error Handling & Logging

1. **Backend Validation & Error Responses**

   * All controllers rely on FormRequest classes (e.g., `ClassRequest`, `EnrollmentRequest`) to validate input. On failure, Laravel returns a 422 with:

     ```json
     {
       "message": "The given data was invalid.",
       "errors": {
         "title": ["The title field is required."],
         "date": ["The date must be a valid date."]
       }
     }
     ```
   * Controllers wrap critical logic in `try/catch` blocks. On unexpected exceptions, `report()` logs the error and returns a 500 JSON response:

     ```json
     { "message": "Server Error" }
     ```

2. **Frontend Error Handling**

   * SWR hooks return `[data, error]`. While `data` is undefined and `error` is undefined, a loading spinner (`<Spinner />`) appears. If `error` is truthy, a toast (e.g., via `react-toastify`) shows `error.response.data.message` or a default “Something went wrong.”
   * Forms using React Hook Form catch field-level validation errors returned from the API and display them under each input.

3. **Logging & Monitoring**

   * **Backend**: Laravel logs to `storage/logs/laravel.log`. Key events to record:

     * User registration and login attempts (info/warning).
     * Payment initiation and verification (info).
     * Failed validations and exceptions (error).
   * **Frontend**: Optional console logging for development; production errors rely on Sentry (if configured) to capture unhandled exceptions.

---

### 10. Deployment & Environment Checklist

1. **Backend**

   * Set `APP_ENV=production`, `APP_DEBUG=false`, and set `SESSION_SECURE_COOKIE=true`.
   * Configure `SANCTUM_STATEFUL_DOMAINS` to include the frontend’s domain (e.g., `gravitygym.com`).
   * Ensure `CORS_ALLOWED_ORIGINS` in `config/cors.php` includes the frontend URL.
   * Run `php artisan migrate --force` and `php artisan config:cache`, `php artisan route:cache`.
   * Start queue worker: `php artisan queue:work --daemon`.
   * Configure Nginx to point `server_name` to backend domain, forwarding `/api/*` to `php-fpm`.

2. **Frontend**

   * Set `NEXT_PUBLIC_API_URL=https://api.gravitygym.com` in Vercel/Netlify.
   * Run `npm run build` to produce an optimized production build.
   * Serve via Vercel or host on a Node.js server behind Nginx:

     ```bash
     npm install
     npm run build
     npm run start
     ```
   * Configure Nginx to proxy pass `localhost:3000` for frontend domain, handle SSL termination (Let’s Encrypt).

3. **Continuous Integration**

   * **Backend CI** (GitHub Actions):

     ```yaml
     name: Laravel CI
     on: [push, pull_request]
     jobs:
       test:
         runs-on: ubuntu-latest
         steps:
           - uses: actions/checkout@v2
           - name: Set up PHP
             uses: shivammathur/setup-php@v2
             with:
               php-version: '8.1'
           - name: Install dependencies
             run: composer install --no-interaction --prefer-dist
           - name: Copy .env
             run: cp .env.example .env
           - name: Generate App Key
             run: php artisan key:generate
           - name: Run Migrations
             run: php artisan migrate --env=testing --no-interaction
           - name: Run Tests
             run: php artisan test --verbose
     ```
   * **Frontend CI** (GitHub Actions):

     ```yaml
     name: Next.js CI
     on: [push, pull_request]
     jobs:
       build:
         runs-on: ubuntu-latest
         steps:
           - uses: actions/checkout@v2
           - name: Use Node.js
             uses: actions/setup-node@v2
             with:
               node-version: '18'
           - run: npm ci
           - run: npm run lint
           - run: npm run test
           - run: npm run build
     ```

---

### 11. Full Process Summary

1. **Startup**

   * Backend:

     * Load `.env`
     * Run migrations & seeders
     * Start queue worker and schedule tasks
     * Serve API under `/api/*`

   * Frontend:

     * Load `.env.local`
     * Build Tailwind CSS
     * Initialize Axios & SWR
     * Render global layout and fetch authenticated user if cookie exists

2. **User Registration & Login**

   * Frontend sends `POST /api/register` or `POST /api/login`.
   * Backend validates, creates session cookie, returns user JSON.
   * Frontend caches user via SWR, redirects based on role.

3. **Browsing & Filtering Classes**

   * Frontend: `useSWR("/api/classes?filters…")`
   * Backend: `ClassController@index` queries Eloquent models, applies filters, returns paginated JSON.
   * Frontend renders class cards; search bar triggers SWR revalidation.

4. **Class Detail & Enrollment**

   * Frontend: `useSWR("/api/classes/{id}")` for details.
   * Backend: `ClassController@show` returns full class model with relationships.
   * Frontend: “Enroll” button calls `POST /api/enrollments`.
   * Backend: `EnrollmentController@store` creates enrollment or returns `payment_url`.
   * Frontend: If payment needed, redirect to gateway; on return, SWR revalidates `/api/enrollments?user_id={id}`.

5. **Profile & Enrollment Management**

   * Frontend:

     * `/profile/enrollments` → `GET /api/enrollments?user_id={id}`
     * “Cancel” → `DELETE /api/enrollments/{id}` → SWR revalidate.
     * `/profile` → `GET /api/user`, edit form → `PUT /api/user` → SWR revalidate.
     * `/profile/password` → `POST /api/password/change`.

   * Backend:

     * `EnrollmentController@index`, `EnrollmentController@destroy`
     * `UserController@update` validates via `UpdateUserRequest`
     * `PasswordController@change` handles hashing and notification

6. **Instructor Panel**

   * Frontend:

     * `/dashboard/instructor` → `useSWR("/api/instructor/{id}/classes")`
     * “View Enrollments” → `useSWR("/api/classes/{id}/enrollments")`
     * “Mark Attended” → `PUT /api/enrollments/{id}` → SWR revalidate.
     * Create/Edit class → `POST /api/admin/classes` or `PUT /api/admin/classes/{id}` → SWR revalidate instructor’s classes.

   * Backend:

     * `InstructorController@classes`, `EnrollmentController@forClass`
     * `ClassController@store`, `ClassController@update` (shared with AdminController)
     * Notifications dispatched on status change

7. **Admin Dashboard**

   * Frontend:

     * `/dashboard/admin/users` → `useSWR("/api/admin/users")`
     * Edit/Delete user → `PUT /api/admin/users/{id}` or `DELETE /api/admin/users/{id}` → SWR revalidate.
     * `/dashboard/classes` → `useSWR("/api/admin/classes")`
     * `/dashboard/enrollments` → `useSWR("/api/admin/enrollments")`
     * Reporting: `useSWR("/api/admin/reports/attendance")`, `useSWR("/api/admin/reports/revenue")` → display charts.

   * Backend:

     * `Admin\UserController`, `Admin\ClassController`, `Admin\EnrollmentController`, `ReportController`
     * Eloquent aggregates for reports, JSON responses for charts

8. **Notifications & Background Tasks**

   * Frontend: Poll `GET /api/notifications?user_id={id}` every 60 seconds.
   * Backend: `NotificationController@index`, `PUT /api/notifications/{id}/read`
   * Laravel Queued Jobs: `SendEnrollmentConfirmation`, `NotifyInstructorOfNewEnrollment`, `SendSubscriptionExpirationReminder`
   * Scheduled Commands:

     * `php artisan subscription:expire-check` (daily at midnight)
     * `php artisan report:generate` (monthly)
     * `php artisan db:cleanup` (weekly)

9. **Error Handling & Logging**

   * Backend: FormRequest validation returns 422 with error details; unhandled exceptions return 500 with “Server Error.” All errors and info logs go to `storage/logs/laravel.log`.
   * Frontend: SWR loading states display spinners; errors trigger toast notifications.

10. **Deployment**

    * **Backend**:

      1. Build Docker images and start services (`php-fpm`, `nginx`, `mysql`, `redis`).
      2. Set `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`.
      3. Run migrations (`artisan migrate --force`) and caching commands.
      4. Start queue worker (`artisan queue:work --daemon`).
      5. Nginx serves requests at `api.gravitygym.com/api/*`.

    * **Frontend**:

      1. Set `NEXT_PUBLIC_API_URL=https://api.gravitygym.com`.
      2. Run `npm run build` and deploy to Vercel or a Node.js host.
      3. Nginx or CDN points `www.gravitygym.com` to the Next.js app on port 3000.

---

By following this end-to-end flow, GravityGym ensures:

* **Secure Authentication & Session Handling** via Laravel Sanctum.
* **Real-Time Data Synchronization** using SWR’s caching and revalidation.
* **Robust Role-Based Access Control** enforced by middleware and policies.
* **Seamless Enrollment & Payment Processing** through dedicated API endpoints and webhooks.
* **Responsive, Maintainable Frontend** built with Next.js 15, Tailwind CSS, and custom hooks.
* **Scalable Backend** with clean controllers, FormRequests, queued jobs, and scheduled commands.
* **Reliable Error Handling & Monitoring** ensuring both user-friendly messages and detailed logs for developers.

This comprehensive architecture allows new features—such as waitlists, class reviews, or loyalty rewards—to slot seamlessly into existing endpoints, SWR fetchers, and UI components without disrupting the core flows.
