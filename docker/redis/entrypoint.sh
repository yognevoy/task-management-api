#!/bin/sh

# Replace environment variables in the config file
envsubst < /usr/local/etc/redis/redis.conf.template > /usr/local/etc/redis/redis.conf

# Start Redis server with the processed config file
exec redis-server /usr/local/etc/redis/redis.conf