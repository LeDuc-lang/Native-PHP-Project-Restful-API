FROM mysql:8.0

COPY stuff/native_php_restful_api.sql /docker-entrypoint-initdb.d/01-init.sql
