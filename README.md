# Brand Mobile Lumen Wallet Service

### **Description**

This will create a dockerized stack for a Lumen application, consisted of the following containers:
-  **Wallet-webserver**, The Nginx web server container

        Nginx
    
-  **Wallet-runtime**, The Php container

#### **Directory Structure**
```
+-- . <project root>
+-- docker
+-- .gitignore
+-- Dockerfile
+-- docker-compose.yml
+-- readme.md <this file>
```
**Prerequisites:** 

* Depending on your OS, the appropriate version of Docker Community Edition has to be installed on your machine.  ([Download Docker Community Edition](https://hub.docker.com/search/?type=edition&offering=community))

**Installation steps:** 
    **Lumen**
   ```
    $ docker-compose up --build -d
   ```

**Access application url** 
  ```
    http://localhost:8000
   ```

**Default configuration values** 

The following values should be replaced in your `.env` file if you're willing to keep them as defaults:
  ```
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=appdb
    DB_USERNAME=user
    DB_PASSWORD=myuserpass
    AWS_REGION=
    AWS_COGNITO_USER_POOL_ID=
    AWS_COGNITO_CLIENT_ID=
    AWS_COGNITO_CLIENT_SECRET=
    AWS_ACCESS_KEY=
    AWS_SECRET_ACCESS_KEY=
```
