# PHP Coding Standard Configurator (PHPCSC)

If you want that your PHP Coding Standard have some PSR1 sniffs and some PSR2 sniffs, this tool is for you!

PHPCSC let you to easily create a custom coding standard (ruleset.xml) ready to use with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).

## How To

### Install
- install [docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/)
- install [docker-compose](https://docs.docker.com/compose/install/)
- install [git](https://www.digitalocean.com/community/tutorials/how-to-install-git-on-ubuntu-18-04-quickstart)
- clone this repository with `git clone git@github.com:elplaza/phpcs-configurator.git <dir>`
- move to project `<dir>` with `mv <dir>`

### Build
```bash
docker-compose build
```

### Usage
Run PHPCSC and start your Coding Standard configuration:
```bash
docker-compose run phpcs-configurator config
```

Otherwise, you can "enter" inside PHPCSC container:
```bash
docker-compose run phpcs-configurator bash
```
install dependencies:
```bash
composer install
```
start your Coding Standard configuration:
```bash
./bin/phpcs-configurator
```


