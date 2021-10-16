## About Loger [codename: Atmosphere]

Loger (House in french) is a Monolith app using laravel 8, jetstream, intertia, vue3, Tailwindcss and some hand crafted packages [Atmosphere UI](), [Journal](), [Tresurer](), and others.

Loger helps to manage my family house chores things like Meal Planner, Home Finance,

Features:
- Meal Planner
- Random Meal 
- Monthly Budget
- Scheduled Payments Reminders
- Subscriptions
- Transactions
## Demo
.

## Installation

To install Loger, you'll need to clone or download this repo:

```
git clone https://github.com/jesusantguerrero/atmosphere.git project_name
```

Next, we can install Atmosphere with these **4 simple steps**:

### 1. Create a New Database

During the installation we need to use a MySQL database. You will need to create a new database and save the credentials for the next step.

### 2. Copy the `.env.example` file

We need to specify our Environment variables for our application. You will see a file named `.env.example`, you will need to duplicate that file and rename it to `.env`.

Then, open up the `.env` file and update your *DB_DATABASE*, *DB_USERNAME*, and *DB_PASSWORD* in the appropriate fields. You will also want to update the *APP_URL* to the URL of your application.

```bash
APP_URL=http://logger.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atmosphere
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Add Composer Dependencies

Next, we will need to install all our composer dependencies by running the following command:

```php
composer install
```
### 4. Run Migrations and Seeds

We need to migrate our database structure into our database, which we can do by running:

```php
php artisan migrate
```
<br>
Finally, we will need to seed our database with the following command:

```php
php artisan db:seed
```
<br>

🎉 And that's it! You will now be able to visit your URL and see your Atmosphere application up and running.

## License
[MIT license](https://opensource.org/licenses/MIT).
