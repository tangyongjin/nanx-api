docker kill jinwang-api-dev
docker rm   jinwang-api-dev
docker run -itd --rm -p 9009:80 --name jinwang-api-dev -v $PWD:/var/www/html --privileged=true apache2-php7-x-debug
