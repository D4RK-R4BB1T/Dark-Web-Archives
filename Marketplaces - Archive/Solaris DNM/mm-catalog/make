#!/usr/bin/env bash

function cmd_help {
    echo "${YELLOW}Usage:${NC}"
    echo "  ./make COMMAND [options] [arguments]"
    echo
    echo "Unknown commands are passed to the docker-compose binary."
    echo
    echo "${YELLOW}Available commands:${NC}"
    echo "  ${GREEN}docker-compose${NC}  Run docker compose command"
    echo "  ${GREEN}artisan${NC}         Run a Artisan command"
    echo "  ${GREEN}composer${NC}        Run a Composer command"
    echo "  ${GREEN}php${NC}             Run a PHP command"
    echo "  ${GREEN}npm${NC}             Run a NPM command"
    echo "  ${GREEN}mysql${NC}           Start a MySQL CLI session within the 'mysql' container"
    echo "  ${GREEN}redis${NC}           Start a Redis CLI session within the 'redis' container"
    echo "  ${GREEN}shell${NC}           Start a shell session within the application container"
    echo "  ${GREEN}root-shell${NC}      Start a root shell session within the application container"
    echo
    echo "${YELLOW}Examples:${NC}"
    echo "  ${GREEN}./make build${NC}                 Build the application image"
    echo "  ${GREEN}./make up${NC}                    Start the application"
    echo "  ${GREEN}./make up -d${NC}                 Start the application in the background"
    echo "  ${GREEN}./make init${NC}                  Build, start and run basic init scripts"
    echo "  ${GREEN}./make docker-compose up${NC}     The same as ${GREEN}./make up${NC}"
    echo "  ${GREEN}./make stop${NC}                  Stop the application"
    echo "  ${GREEN}./make restart${NC}               Restart the application"
    echo "  ${GREEN}./make ps${NC}                    Display the status of all containers"
    echo "  ${GREEN}./make artisan key:generate${NC}  Generate new application key"
    echo "  ${GREEN}./make composer validate${NC}     Validates a composer.json and composer.lock"
    echo "  ${GREEN}./make clean-cnt${NC})            Stop and remove all containers"
    echo "  ${GREEN}./make clean-vol${NC}             Removes stored info from volumes"
    echo "  ${GREEN}./make clean-img${NC}             Removes image files"
    echo "  ${GREEN}./make clean-all${NC}             All three above"
}

function cmd_docker-compose {
    $DOCKER_COMPOSE "$@"
}

function cmd_docker {
    $DOCKER "$@"
}

function cmd_php {
    cmd_docker-compose exec "$EXEC_NOTTY" "$APP_SERVICE" php "$@"
}

function cmd_npm {
    cmd_docker-compose exec "$EXEC_NOTTY" "$APP_SERVICE" npm "$@"
}

function cmd_artisan {
    cmd_php artisan "$@"
}

function cmd_composer {
    cmd_docker-compose exec "$EXEC_NOTTY" "$APP_SERVICE" composer "$@"
}

function cmd_mysql {
    cmd_docker-compose exec "$EXEC_NOTTY" mysql bash -c \
        "MYSQL_PWD=\$MYSQL_PASSWORD mysql -u \$MYSQL_USER \$MYSQL_DATABASE"
}

function cmd_redis {
    cmd_docker-compose exec "$EXEC_NOTTY" redis redis-cli
}

function cmd_shell {
    APP_SERVICE=${1:-app}
    cmd_docker-compose exec "$APP_SERVICE" sh
}

function cmd_root-shell {
    APP_SERVICE=${1:-app}
    cmd_docker-compose exec -u root "$APP_SERVICE" sh
}

function cmd_clean-cnt {
    cmd_docker-compose stop app mysql phpmyadmin redis web tor
    cmd_docker-compose rm app mysql phpmyadmin redis web tor
}

function cmd_clean-vol {
    cmd_docker volume rm mm-catalog_mysql-store mm-catalog_redis-store
}

function cmd_clean-img {
    IMAGES_TO_REMOVE=()
    IMG_CATALOG_APP=$(docker images catalog -q)
    IMG_REDIS=$(docker images redis -q)
    IMG_SQL=$(docker images percona -q)

    if [ ! -z $IMG_CATALOG_APP ]; then
        IMAGES_TO_REMOVE[${#IMAGES_TO_REMOVE[@]}]=$IMG_CATALOG_APP
    fi

    if [ ! -z $IMG_REDIS ]; then
        IMAGES_TO_REMOVE[${#IMAGES_TO_REMOVE[@]}]=$IMG_REDIS
    fi

    if [ ! -z $IMG_SQL ]; then
        IMAGES_TO_REMOVE[${#IMAGES_TO_REMOVE[@]}]=$IMG_SQL
    fi

    if [ ! -z IMAGES_TO_REMOVE ]; then
        cmd_docker rmi -f $IMAGES_TO_REMOVE
    fi
}

function cmd_clean-all {
    cmd_clean-cnt
    cmd_clean-vol
    cmd_clean-img
}

function cmd_init {
    cmd_docker-compose up -d
    cmd_docker-compose exec "$APP_SERVICE" php /app/artisan catalog:init
}

set -e

pushd "${BASH_SOURCE%/*}" > /dev/null

# Determine if stdout is a terminal
if test -t 1; then
    # Determine if colors are supported
    ncolors=$(tput colors)

    if (( ncolors  > 7)); then
        BOLD="$(tput bold)"
        YELLOW="$(tput setaf 3)"
        GREEN="$(tput setaf 2)"
        NC="$(tput sgr0)"
    fi
fi

if (( $# < 1 )) || [ "$1" == "help" ] || [ "$1" == "-h" ] || [ "$1" == "--help" ]; then
    cmd_help
    exit
fi

if [ -x "$(command -v docker-compose)" ]; then
    DOCKER_COMPOSE="docker-compose"
else
    DOCKER_COMPOSE="docker compose"
fi

APP_SERVICE=${APP_SERVICE:-"app"}
DOCKER="docker"

test -t 0 || EXEC_NOTTY="-T"

if [ -f ./vars ]; then
    source ./vars
fi

if ! docker info > /dev/null 2>&1; then
    echo "${BOLD}Docker is not running.${NC}" >&2
    exit 1
fi

cmd="$1"
if [[ "$cmd" == @(docker-compose|artisan|composer|php|npm|mysql|redis|shell|root-shell|clean-cnt|clean-vol|clean-img|clean-all|init) ]]; then
    shift 1
    "cmd_$cmd" "$@"
else
    cmd_docker-compose "$@"
fi