# ProfildienstImport

This is the import tool for the "Online Profildienst". It handles the
import and update of titles, users and covers. The import tool is
distributed as a [PHAR](http://php.net/manual/de/phar.using.intro.php) file.

## System requirements
* PHP 7 CLI version (check with `php -v` on terminal)
* Global [Composer](https://getcomposer.org/doc/00-intro.md#globally) installation
* Global [phar-composer](https://github.com/clue/phar-composer) installation
* If you want to run the unit tests, you'll need [phpunit](https://phpunit.de/)

## Installation
1. Open a terminal and navigate to the root dir of the ProfildienstImport (the dir this file is in)
2. Run `php -d phar.readonly=off build.php` to create the PHAR file
3. (Optional) if you want to deploy the created PHAR file to the production server, run `deploy.sh`

## Running the tests
If you want to run the unit tests, simply execute `phpunit` in the ProfildienstImport root dir.

## Usage
Run `./ProfildienstImport.phar` to see a list of all available commands.
Please refer to the corresponding Confluence page for a detailed usage description.