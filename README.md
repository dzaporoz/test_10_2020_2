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
docker build -t test_task .
```
To run the application without docker - install composer dependencies:

```
git clone https://github.com/dzaporoz/test_10_2020_2.git
cd test_10_2020_2
composer install
```

## Running
1.1.1 To run php built-in server from a container execute:
```
docker run -p 8080:80 test_task php -S 0.0.0.0:80 public/index.php
```
1.1.2. To run php built-in server without docker:
```
php -S localhost:8080 public/index.php
```
1.2. Web version will be available at 'http://localhost:8080' URL

1.3. To stop web server press Ctrl+C in console

2.2 To run grabber execute following command:
```
docker run test_task php bin/cli.php grab
```
or
```
php bin/cli.php grab
```

2.3 To reset database run migration by command:
```
docker run test_task php bin/cli.php migrate
```
or
```
php bin/cli.php migrate
```
