#!/bin/sh
set -e

if env -i REQUEST_METHOD=GET SCRIPT_NAME=/ SCRIPT_FILENAME=/ cgi-fcgi -bind -connect 127.0.0.1:9000; then
	exit 0
fi

exit 1
