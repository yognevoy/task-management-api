FROM redis:7-alpine

RUN apk add --no-cache gettext

COPY redis.conf /usr/local/etc/redis/redis.conf.template
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 6379

ENTRYPOINT ["/entrypoint.sh"]
