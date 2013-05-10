#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

VERSION=1.3.0
NAME=ArangoDB-$VERSION

if [ ! -d "$DIR/$NAME" ]; then
  # download ArangoDB
  echo "wget http://www.arangodb.org/travisCI/$NAME.tar.gz"
  wget http://www.arangodb.org/travisCI/$NAME.tar.gz
  echo "tar zxf $NAME.tar.gz"
  tar zvxf $NAME.tar.gz
fi

ARCH=$(arch)
PID=$(echo $PPID)
TMP_DIR="/tmp/arangodb.$PID"
PID_FILE="/tmp/arangodb.$PID.pid"
ARANGODB_DIR="$DIR/$NAME"

ARANGOD="${ARANGODB_DIR}/bin/arangod"
if [ "$ARCH" == "x86_64" ]; then
  ARANGOD="${ARANGOD}_x86_64"
fi

# create database directory
mkdir ${TMP_DIR}

echo "Starting arangodb '${ARANGOD}'"

${ARANGOD} \
    --database.directory ${TMP_DIR}  \
    --configuration none  \
    --server.endpoint tcp://127.0.0.1:8529 \
    --javascript.startup-directory ${ARANGODB_DIR}/js \
    --server.admin-directory ${ARANGODB_DIR}/html/admin \
    --javascript.modules-path ${ARANGODB_DIR}/js/server/modules:${ARANGODB_DIR}/js/common/modules:${ARANGODB_DIR}/js/node \
    --javascript.package-path ${ARANGODB_DIR}/js/npm:${ARANGODB_DIR}/js/common/test-data/modules \
    --javascript.action-directory ${ARANGODB_DIR}/js/actions  \
    --database.maximal-journal-size 1048576  \
    --server.disable-admin-interface true \
    --server.disable-authentication true \
    --javascript.gc-interval 1 &

sleep 2

echo "Check for arangod process"
process=$(ps auxww | grep "bin/arangod" | grep -v grep)

if [ "x$process" == "x" ]; then
  echo "no 'arangod' process found"
  echo "ARCH = $ARCH"
  exit 1
fi

echo "Waiting until ArangoDB is ready on port 8529"
while [[ -z `curl -s 'http://127.0.0.1:8529/_api/version' ` ]] ; do
  echo -n "."
  sleep 2s
done

echo "ArangoDB is up"