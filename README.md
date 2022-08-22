## About Loger [codename: Atmosphere] (Work in progress)

Loger (House in french) is a Monolith app using laravel 8, jetstream, intertia, vue3, Tailwindcss and some hand crafted packages [Atmosphere UI](https://github.com/insane-code/journal), [Journal](https://github.com/insane-code/journal), [Tresurer](https://github.com/jesusantguerrero/insane-treasurer), and others.

Loger helps to manage my family house chores things like Meal Planner, Home Finance,

Features:
- Meal Planner
- Random Meal 
- Monthly Budget
- Scheduled Payments Reminders
- Subscriptions
- Transactions
## Showcase

![Landing](/public/images/landing.png)

Dashboard Expanded 
![Dashboard](/public/images/full-sized-dashboard.png)

Dashboard
![Dashboard](/public/images/dashboard-small-menu.png)

Meal Planner
![Meal Planner](/public/images/meal-planner.png)

Finance Overview
![Finance Overview](/public/images/finance-overview.png)

Budget
![Budget](/public/images/budget.png)



### Prerequisites

| Prerequisite                                          | Version     |
| ------------------------------------------------------| ----------  |
| [Docker*]()                                           |    --       |
| [Node.js](http://nodejs.org)                          | `~ ^14.18.0`|
| npm (comes with Node) or yarn (used)                  | `~ ^5`      |
| [PHP]                                                 | `~ ^8.1.2`  |
| [Composer](https://getcomposer.org/)                  | ' ^2.3.8    |
| [Cloud Platform Project (with Gmail API)**](https://developers.google.com/gmail/api/quickstart/js)                                |    --                                                 |             |
| PHP extension ext-mailparse**                         |      --     |

`* Docker is optional and have all the dependencies you need ext-mailparse included`
`** Those requirements are optional for Gmail integration/automation`

```shell
node -v
php -v
```

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
APP_URL=http://127.0.0.1:8000/

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loger
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Add Composer Dependencies
```php
composer install --ignore-platform-reqs
```
### 4. Run Migrations and Seeds

```bash
php artisan migrate
php artisan db:seed
php artisan journal:set-accounts
```
<br>

backend development
```bash
php artisan serve
```
Frontend development
The bachend have to be running

```bash
# install npm packages
npm install
# development
npm run dev
```

🎉 And that's it! You will now be able to visit your URL and see your Atmosphere application up and running.

## License
[MIT license](https://opensource.org/licenses/MIT).
