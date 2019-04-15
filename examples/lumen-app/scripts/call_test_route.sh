#!/usr/bin/env bash
length=${TIMES_TO_CALL_EXAMPLE_ROUTE}

for ((i=1;i<=$length;i++));
do
   curl -v --header "Connection: keep-alive" "http://localhost:${EXAMPLE_APP_NGINX_PORT}/test";
done
