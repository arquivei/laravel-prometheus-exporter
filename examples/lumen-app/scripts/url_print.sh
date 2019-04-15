#!/bin/bash

echo -e "\n"
echo "Lumen Example App:            http://localhost:${EXAMPLE_APP_NGINX_PORT}/"
echo "Lumen Example App test route: http://localhost:${EXAMPLE_APP_NGINX_PORT}/test"
echo "MySQL:                        http://localhost:${EXAMPLE_APP_MYSQL_PORT}/"
echo "Redis:                        http://localhost:${EXAMPLE_APP_REDIS_PORT}/"
echo "Prometheus:                   http://localhost:${EXAMPLE_APP_PROMETHEUS_PORT}/"
echo "Grafana:                      http://localhost:${EXAMPLE_APP_GRAFANA_PORT}/"
echo "Push Gateway:                 http://localhost:${EXAMPLE_APP_PUSH_GATEWAY_PORT}/"
