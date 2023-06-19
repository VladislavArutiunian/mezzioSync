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
This project is about syncing contacts from Kommo crm system to Unisender mail service.

On every action (creating, updating, deleting) with contacts, it will be (created, updated, deleted) on Unisender service

This app supports multi users usage, just follow steps below to add any amount users you want to the sync process

And also uses beanstalkd for
О проекте что делает, кратко о функционале

## Usage:
#### Initial steps:

First of all you need start docker containers: ```docker compose up -d --build```

Then start local server: ```composer serve```

Install localtunnel ([docs](https://theboroer.github.io/localtunnel-www/))

Run localtunnel: ```lt --port 8080 --subdomain <project_name> --print-requests --allow-invalid-cert```

Install composer inside docker application-backend (*recommended*) - ```composer install --ignore-platform-reqs```

Run migrations inside docker application-backend (*recommended*) - ```composer exec phpmig migrate```

#### Integration steps:
To interact with those services API's you need:
 - sign in/up at [kommo.com](https://www.kommo.com/) and create integration in your account settings ([docs](https://www.kommo.com/developers/content/integrations/starting_the_work/))

 - get your API-key from Unisender ([docs](https://www.unisender.com/ru/support/api/common/api-key/))

#### Setup application steps:
Once you get your integrations credentials you need to save it into your database

Perform POST request with params *account id*, *secret key*, *client id*, *url*

to endpoint **/setup** - to save Kommo credentials into your Integrations table
and account id into Accounts table

#### Authorization and getting kommo access token:

This action can be done by 2 ways:
1. With Standart Authorization

   Perform GET request to endpoint **/auth**, provide Kommo account id,
   
   you will be redirected to kommo.com, tab button to give permissions for integration
2. With Simple Authorization
   
   Just perform GET request with auth code from integration (for you to be informed, it is available 20 min)
   
   By this sample: /auth?code=<your_code>&referer=<your_referer>&platform=2&client_id=<your_client_id>&from_widget=1

Eventually, kommo access token will be saved in Accesses table

#### Launching application:
Finally, you need to install the widget (integration with front part) into Kommo integration.

Take Widget.zip archive from a root of this project and upload it to your integration
([sample image](https://pcfcdn.kommo.com/static/images/pages/developers/integration/create-personal-integration.png))

After installing, widget will ask you to provide your Unisender API-key, *now your contacts synchronization is working!*

#### Actions under the hood:
After first launching, widget sends POST request with API-key to /widget endpoint.

Saves in Accesses db table.

Importing contacts to unisender.
And subscribes on webhook.

Now every new action with contacts will be handled by endpoint */webhook*.
