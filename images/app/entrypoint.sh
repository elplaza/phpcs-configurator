#!/bin/bash

# check if directory is "mounted" correctly
if [ -z "$(ls -A $PWD)" ]; then
	echo "the folder '$PWD' is empty"
	echo "Please install app in '$PWD'"
	exit 1
fi


case "$1" in
	config)
		composer install

		./bin/phpcs-configurator
		;;

	bash)
		/bin/bash
		;;

	*)
		echo "This container accepts the following commands:"
		echo "- config"
		echo "- bash"
		exit 1
esac