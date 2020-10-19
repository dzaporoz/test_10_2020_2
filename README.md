# Test task

## Subject
Grab (programmatically) first 15 articles from rbc.ru (block to parse specified separately) and put in database (create structure by yourself) or file.
Output all news with cutting text to 200 characters as description, with link to full news at button "details". At full news page output picture if available.

## Requirements
- docker
- or composer and php-cli with required modules to run the application without the container

## Installation
Clone project and build docker image
```
git clone https://github.com/dzaporoz/test_10_2020_2.git
cd test_10_2020_2
docker build -t test_image .
```
To run the application without docker - install composer dependencies:

```
git clone https://github.com/dzaporoz/test_10_2020_2.git
cd test_10_2020_2
composer install
```

## Running
1.1.1 To run container with php built-in server execute:
```
docker run -d -p 8080:80 --name test_container test_image
```
1.1.2. To run php built-in server without docker:
```
php -S localhost:8080 public/index.php
```
1.2. Web version will be available at 'http://localhost:8080' URL

2.2 To run grabber execute following command:
```
docker exec test_container php bin/cli.php grab
```
or
```
php bin/cli.php grab
```

2.3 To reset database run migration by command:
```
docker exec -i test_container php bin/cli.php migrate
```
or
```
php bin/cli.php migrate
```

## Removing
To remove docker application run:
```
docker stop test_container
docker rm test_container
docker image rm test_image
```
