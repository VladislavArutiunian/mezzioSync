## Application microservice for contact synchronization between Kommo and Unisender services

 Tech stack: 
 - Laminas Mezzio Framework,
 - MySQL,
 - Eloquent ORM,
 - phpmig,
 - beanstalkd,
 - cronitor,
 - docker,
 - ngrok/localtunnel

Versions:
- composer - 2.3.5
- php - 7.4

## About:
О проекте что делает, кратко о функционале

## Usage:
#### Initial steps:

First of all you need start docker containers: ```docker compose up -d --build```

Then start local server: ```composer serve```

Install localtunnel ([docs](https://theboroer.github.io/localtunnel-www/))

Run localtunnel: ```lt --port 8080 --subdomain <project_name> --print-requests --allow-invalid-cert```

Run migrations inside docker application-backend (*recommended*) - ```composer exec phpmig migrate```

#### Integration steps:
To interact with those services API's you need:
 - sign in/up at [kommo.com](https://www.kommo.com/) and create integration in your account settings ([docs](https://www.kommo.com/developers/content/integrations/starting_the_work/))

   Save *account id*, *secret key*, *client id*, *url* into your integrations table
 - get your API-key from Unisender ([docs](https://www.unisender.com/ru/support/api/common/api-key/))

#### Final setup application steps:
Once you get your integrations creditials you need to save it into your database
