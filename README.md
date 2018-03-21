# Amazee Labs CLI Tools

## Introduction
The JIRA web user interface can be frustrating to work with but - more importantly - jumping into a web interface while working on deep work creates an opportunity for distraction. The goal of this project is to create a set of console tools that people can use to interact with JIRA without needing to open the web browser. While this is primarily focused on people that do development work, the tool can easily be used by anyone that can run PHP-cli.

## Installation
The installation at this point is very basic, and will ultimately have a simple installer. For now the following steps can be taken.

 - Clone this repository
 - Run `composer install`
 - Run `php artisan` for list of commands
 - Optionally runs something like `sudo ln -s <repository path>/artisan /usr/local/bin`
 - Create your own .env from the .env.example
 - Create your own ~/alcli.yml file
 
  
The objective is that you can have different alcli.yml files for different projects. So eventually you might be able to run `php artisan jira:ls mine` in a specific project directory to get the tickets assigned to you for just an a project cloned to a different repository. Currently the yml file in your local directory will take precedence, followed by the home directory, and then the <repository path>

## Multiple Environments
You can load specific .env files and alcli.yml files by changing the APP_ENV environment variable. The following rules are followed:
 - If .env.$APP_ENV file exists, that is used instead of the standard ".env" file
 - If .env.$APP_ENV does not exist, ".env" is used
 - If ${APP_ENV}_alcli.yml exists in the current directory, it is loaded for config variables
 - If ${APP_ENV}_alcli.yml exists in the home directory, it is loaded for config variables
 - If ${APP_ENV}_alcli.yml exists in the <repository path> directory, it is loaded for config variables
 - If ${APP_ENV}_alcli.yml does not exist in the current directory, alcli.yml from the current directory is checked and is loaded for config variables if found
 - If ${APP_ENV}_alcli.yml does not exist in the home directory, alcli.yml from the home directory is checked and is loaded for config variables if found
 - If ${APP_ENV}_alcli.yml does not exist in the <repository path>, alcli.yml from the <repository path> is checked and is loaded for config variables if found

 
## Overwriting alcli.yml entries
 As above, when you run the artisan commands, the code will check three places for a ${APP_ENV}_alcli.yml and  alcli.yml file
  - The current directory
  - Your home directory
  - The <respository path> where the artisan command lives
 
## Architecture
The software is built on Lumen, and leans heavily on 
 - Lumen: https://lumen.laravel.com/docs/5.6
 - Laravel's artisan: https://laravel.com/docs/5.6/artisan
 - Chobie's JIRA Rest Client: https://github.com/chobie/jira-api-restclient
 - Bomoko's (https://github.com/bomoko) and ALGM's work on the Amazee Labs Business Automation work built on Lumen: https://github.com/AmazeeLabs/business-automation

## alcli.yml

Most of the heavy lifting for this functionality lives in app/Jira.php at the moment.

### Issues
This section allows you to map a word to a jira issue key. For example, you could run `php artisan jira:lw standup --time=15m --comment="Meeting time"`. In this example the work "standup" is mapped to the issue key in the yml file.

### Users
Similar to the way that issues work, this lets you forget about the jira username, and use friendly names. For example `s.j.haroldson` on Jira can become `susan` for your commands.

### Queries 
Queries are a quick way to list groups of tickets. The structure is to map a name to a JQL stanza. Right now there is no variable replacement performed. It is just a simple lookup.


