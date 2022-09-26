# Loger 🏡

<h3 align="center">
    The Digital Home Management Software
</h3>

<!-- <p align="center">
	<strong>
		<a href="https://loger.neatlancer.com/">Website</a>
		•
		<a href="https://loger.neatlancer.com">Docs</a>
		•
		<a href="https://loger.neatlancer.com">Demo</a>
	</strong>
</p> -->
> Status: Alpha

> Codename: Atmosphere
## About Loger

Loger (House in french) as family managing home is almost like being CEO 
of a company. There's a lot of things going on and that you have to keep in mind.
Things like **budget**, **expenses**, **subscriptions** to keep healthy financial habits 
managing goals/savings like **emergency fund** or planning your next vacations or Christmas;
having a **Meal Plan**, grocery list generated with the things you need... well, you get the point.

All the things mentioned above are part of our family/personal routine. We do it in our minds or paper
or if you are like me with different software. Loger aims to be a central point to manage all that an more.

## ✨Features:

Loger is organized in "concerns" by the moment it have 4

* ### 💵 Finance:
    - [x] Monthly Budget
    - [x] Watchlists
    - [x] Accounts
    - [x] Transactions
    - [x] Statistics/Net Worth
    - [ ] Scheduled Transactions*

* ### 🍗 Meal Planner
    - [x] Recipes   
    - [x] Ingredients
    - [x] Meal Planner
    - [x] Random Meal Generator
    - [ ] Menus*

* ### 👨‍👩‍👧 Relationship**
    I don't want to automate/digitalize the humanity of a relationship here but save reminders of activities that would strengthen it like:

    - Goals/Projects like travels and activities 

* ### 🏡Home/Family Projects**
    There are things that need an action but not immediately, maybe we just need to save then to take it to the budget in the next months or three months whatever.

    - New furniture
    - House Repairs/Improvements
    - Events and activities


>  (*) Feature is planned but not finished yet
(**) Section is considered but not planned still in research

<!-- ## Demo

<a href="https://cloud.digitalocean.com/apps/new?repo=https://github.com/jesusantguerrero/atmosphere/tree/staging" target="_blank"><img src="https://www.deploytodo.com/do-btn-blue.svg" width="240" alt="Deploy to DO"></a> -->

## Showcase

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

## Motivation
- This was an Idea initially considered as a part of a 12x12 SaaS challenge.
- I married and needed something like this to keep my things organized and planned.
- Financial software had fixed categories, bank sync didn't work in my country or just. worked more like expense tracker than budget (Except YNAB).
- I didn't want to have multiple apps to manage things of the same context and need a relation (Budgeting app, calendars, meal planner, custom notion template).


## Technical Stuff
Loger is a Monolith app using laravel 9, jetstream, inertia, vue3, Tailwindcss and some hand crafted packages [Atmosphere UI](https://github.com/jesusantguerrero/atmosphere-ui), [Journal](https://github.com/insane-code/journal), and others.


| Prerequisite                                          | Version     |
| ------------------------------------------------------| ----------  |
| [Node.js](http://nodejs.org)                          | `~ ^14.18.0`|
| npm (comes with Node) or yarn (used)                  | `~ ^5`      |
| [PHP]                                                 | `~ ^8.1.2`  |
| [Composer](https://getcomposer.org/)                  | ' ^2.3.8    |
| [MariaDB](https://mariadb.org/)***                    |  `10.8.4`   |
| [Cloud Platform Project (with Gmail API)**](https://developers.google.com/gmail/api/quickstart/js)                                |    --                                                 |             |
| PHP extension ext-mailparse**                         |      --     |

`** Those requirements are optional for Gmail integration/automation`
`*** MariaDB could be replaced with MySql8`

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
[BSD-3 license](https://github.com/jesusantguerrero/atmosphere/blob/master/LICENSE).

## Author
Jesus Guerrero
- website: [jesusantguerrero.com](https://jesusantguerrero.com)
- twitter: [@jesusntguerrero](https://twitter.com/jesusntguerrero) 
