echo "brew services  start mysql" 

 

( docker stop nanx-api-dev > /dev/null && echo Stopped container nanx-api-dev && \
  docker rm nanx-api-dev ) 2>/dev/null || true


docker run  -itd --rm  -p 9009:80 --name nanx-api-dev  -v $PWD:/var/www/html --privileged=true  p8a
