<p align="center">
  <a href="https://loger.neatlancer.com" target="_blank" rel="noopener noreferrer">
    <img width="160" height="100" src="./public/logo.svg" alt="Loger logo">
  </a>
  <sup><em>beta</em></sup></
</p>

<h3 align="center">
    The Digital Home Management Software
</h3>

 <p align="center">
	<strong>
		<a href="https://loger.vercel.app/" target="_blank">Website</a>
		•
		<a href="https://loger.vercel.app/docs" target="_blank">Docs</a>
		•
		<a href="https://loger.neatlancer.com" target="_blank">Demo</a>
	</strong>
</p>

![example workflow](https://github.com/jesusantguerrero/atmosphere/actions/workflows/laravel.yml/badge.svg)

![example workflow](https://github.com/jesusantguerrero/atmosphere/actions/workflows/laravel.yml/badge.svg?event-push)


![image](https://user-images.githubusercontent.com/17421742/212417292-19f319c5-1cf4-48a8-ba40-1e9b040e820f.png)

## About Loger

Loger (House in French) as a family managing home is almost like being CEO of a company. There are a lot of things going on that you have to keep in mind. Things like budgeting, expenses, subscriptions to keep healthy financial habits managing goals/savings like an emergency fund or planning your next vacation or Christmas; having a Meal Plan, grocery list generated with the things you need... well, you get the point.

All the things mentioned above are part of our family/personal routine. We do it in our minds or on paper or if you are like me with different software. Loger aims to be a central point to manage all that an more.

## ✨Features:

Loger is organized in "concerns" by the moment it has 4

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
    - [x] Chores
    - [x] Occurrence Checks
    - [x] Plans (Events and activities / Repairs / ETC)
        - [ ] Quick Create Budget from plans
        - [ ] Quick Create Transactions from plans? 
    - [x] Equipment 

>  (*) Feature is planned but not finished yet
(**) The section is considered but not planned still in research


## Modules
- Plan module: (home/projects) 
- Watchlist module: (finance)

<!-- ## Demo

<a href="https://cloud.digitalocean.com/apps/new?repo=https://github.com/jesusantguerrero/atmosphere/tree/staging" target="_blank"><img src="https://www.deploytodo.com/do-btn-blue.svg" width="240" alt="Deploy to DO"></a> -->

## Showcase

## Demo
View a live [demo here](https://loger.neatlancer.com), or deploy your instance to DigitalOcean, by clicking the button below.

<a href="https://cloud.digitalocean.com/apps/new?repo=https://github.com/jesusantguerrero/atmosphere/tree/master" target="_blank">
 <img src="https://www.deploytodo.com/do-btn-blue.svg" width="240" alt="Deploy to DO">
</a>

### Dashboard 
![Dashboard](https://user-images.githubusercontent.com/17421742/212417292-19f319c5-1cf4-48a8-ba40-1e9b040e820f.png)

### Meals
![Meals Section](https://user-images.githubusercontent.com/17421742/212419093-5c589843-4f2d-4058-b2e6-165c8d9ceaa5.png)


### Finance Overview
![Finance Overview](https://user-images.githubusercontent.com/17421742/212419763-90dab10b-3c48-46cd-ba79-6e1cc155980f.png)

### Budget & Goals
![Budget & Goals](https://user-images.githubusercontent.com/17421742/212420274-1c361875-916d-4cd0-9c94-69b6d1c850b7.png)

### Housing
![image](https://user-images.githubusercontent.com/17421742/212420956-322378e0-d491-4259-ba93-8a6f68dec803.png)


## Motivation
- This was an Idea initially considered as a part of a 12x12 SaaS challenge.
- I married and needed something like this to keep my things organized and planned.
- Financial software had fixed categories, and bank sync didn't work in my country or just. worked more like an expense tracker than a budget (Except YNAB).
- I didn't want to have multiple apps to manage things of the same context and need a relation (Budgeting app, calendars, meal planner, custom notion template).


## Technical Stuff
Loger is a Monolith app using Laravel 9, jetstream, inertia, vue3, Tailwindcss, and some handcrafted packages [Atmosphere UI](https://github.com/jesusantguerrero/atmosphere-ui), [Journal](https://github.com/insane-code/journal), and others.


| Prerequisite                                          | Version     |
| ------------------------------------------------------| ----------  |
| [Node.js](http://nodejs.org)                          | `~ ^20.9.0`|
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
git clone https://github.com/jesusantguerrero/atmosphere.git loger
```

Next, we can install Atmosphere with these **4 simple steps**:

### 1. Copy the `.env.example` file

We need to specify the Environment variables for our application. You will see a file named `.env.example`, you will need to duplicate that file and rename it to `.env`.

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

### 2. Install dependencies
```php
composer install --ignore-platform-reqs;
```
### 3. Install loger

```bash
yarn app:install"
```

### 4. Add sample data(optional)

```bash
php artisan app:demo-seed
```
<br>

backend development
```bash
php artisan serve
```
Frontend development
The backend has to be running

```bash
npm run dev
```

🎉 And that's it! You will now be able to visit your URL and see your Atmosphere application up and running.

## License
[BSD-3 license](https://github.com/jesusantguerrero/atmosphere/blob/master/LICENSE).

## Author
Jesus Guerrero
- website: [jesusantguerrero.com](https://jesusantguerrero.com)
- twitter: [@jesusntguerrero](https://twitter.com/jesusntguerrero) 
