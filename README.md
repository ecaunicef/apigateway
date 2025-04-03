
## About Api Gateway

API gateway designed to streamline client-service interactions within a microservices architecture. It serves as the single entry point for client requests, receiving each one, identifying the appropriate microservice, and efficiently routing the request to the intended service. Once the microservice processes the request, Api Gateway forwards the response back to the client, ensuring a seamless, fast, and secure exchange. Additionally, Api Gateway manages essential session and cookie handling, providing stateful support for client sessions without burdening individual microservices. This allows Api Gateway to enhance security, simplify service discovery, and offer a smoother, more consistent user experience by centralizing these tasks at the gateway level.


# Project Setup Guide

Follow the steps below to set up and run the project locally.

## 1. Clone the Repository

Clone the repository using Git:

```bash
git clone <repo-url>
```

## 2. Navigate to the Project Directory
Move into the project folder:

```bash
cd <repo-directory>
```

## 3. Install PHP Dependencies
Install the PHP dependencies using Composer:

```bash
composer install
```


## 4. Copy the .env File
Copy the .env.example file to .env:

```bash
cp .env.example .env
```

## 5. Update the Environment Variables
Update the following credentials in the .env file:

Database credentials:

DB_HOST=localhost
DB_PORT=27017
DB_DATABASE=<DB_DATABASE>
DB_USERNAME=<DB_USERNAME>
DB_PASSWORD=<DB_PASSWORD>


Set the correct ports for data import and data supplier services:
```bash
DATAPROCESSING_SERVICE_BASE_URL=http://localhost:9091
DATARETRIEVAL_SERVICE_BASE_URL=http://localhost:9092
```

## 6. Clean Cache and Config
Clear any cache and config to make sure everything is up to date:

```bash
php artisan config:clear
php artisan cache:clear
```

## 7. Navigate to the Public Folder
To check if the project works:

http://localhost/project-folder/public


## Contributor

iTM CodeHawk Team