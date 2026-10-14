#!/bin/sh
set -eu
health_host="$(php -r 'echo parse_url(getenv("APP_URL") ?: "http://localhost", PHP_URL_HOST) ?: "localhost";')"
exec curl --fail --silent --show-error --max-time 8 -H "Host: $health_host" http://127.0.0.1/up
