FROM node:12.13.0-alpine
RUN apk add --no-cache su-exec=0.2-r0 \
    && addgroup bar \
    && adduser -D -h /home -s /bin/sh -G bar foo
COPY entrypoint.sh /entrypoint
ENTRYPOINT ["/entrypoint"]
