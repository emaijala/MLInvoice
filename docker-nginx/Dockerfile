FROM alpine:latest

RUN apk update
RUN apk --no-cache add nginx  php83-mbstring php83-fpm php83-mysqli php82-mysqlnd php83-session curl unzip

WORKDIR /work

RUN curl -O https://labs.fi/files/mlinvoice-2.1.2.zip
RUN unzip mlinvoice-2.1.2.zip
RUN rm mlinvoice-2.1.2.zip

RUN sed -ri \
 -e "s/^user = nobody/user = www/" \
 -e "s/^group = nobody/group = www/" /etc/php83/php-fpm.d/www.conf


RUN addgroup -g 2024 www
RUN adduser -S -D www -G www 

RUN chown www:www /work/mlinvoice /work/mlinvoice/config.*

COPY mlinvoice-nginx.conf /etc/nginx/nginx.conf
COPY entrypoint /work/entrypoint

EXPOSE 80

HEALTHCHECK --interval=5m CMD curl -f http://localhost || exit 1

ENTRYPOINT ["sh", "/work/entrypoint"]
