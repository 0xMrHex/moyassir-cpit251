FROM php:8.2-cli
WORKDIR /app
COPY . /app
RUN mkdir -p /app/data && chmod -R 777 /app/data
EXPOSE 8088
CMD ["php", "-S", "0.0.0.0:8088", "-t", "/app/src"]
