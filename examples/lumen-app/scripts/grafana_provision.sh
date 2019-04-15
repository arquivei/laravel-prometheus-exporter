#!/bin/bash

envsubst < "./grafana/prometheus_datasource.template" > "./grafana/prometheus_datasource.json"

curl -X "POST" "http://grafana:3000/api/datasources" \
    -H "Content-Type: application/json" \
     --user admin:admin \
     -d @./grafana/prometheus_datasource.json

echo -e "\n"

curl -X "POST" "http://grafana:3000/api/dashboards/db" \
    -H "Content-Type: application/json" \
     --user admin:admin \
     -d @./grafana/dashboard.json

echo -e "\n"
