#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

VERSION="3.1-nightly"
NAME="ArangoDB-$VERSION"

if [ ! -d "$DIR/$NAME" ]; then
  # download ArangoDB
  echo "wget --no-check-certificate http://www.arangodb.com/repositories/nightly/travisCI/$NAME.tar.gz"
  wget --no-check-certificate http://www.arangodb.com/repositories/nightly/travisCI/$NAME.tar.gz
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

echo "Starting ArangoDB '${ARANGOD}'"

${ARANGOD} \
    --database.directory ${TMP_DIR} \
    --configuration none \
    --server.endpoint tcp://127.0.0.1:8529 \
    --javascript.startup-directory ${ARANGODB_DIR}/js \
    --javascript.app-path ${ARANGODB_DIR}/js/apps \
    --database.maximal-journal-size 1048576 \
    --database.force-sync-properties false \
    --server.authentication true &

sleep 2

echo "Check for arangod process"
process=$(ps auxww | grep "bin/arangod" | grep -v grep)

if [ "x$process" == "x" ]; then
  echo "no 'arangod' process found"
  echo "ARCH = $ARCH"
  exit 1
fi

echo "Waiting until ArangoDB is ready on port 8529"

n=0
timeout=60
while [[ (-z `curl -H 'Authorization: Basic cm9vdDo=' -s 'http://127.0.0.1:8529/_api/version' `) && (n -lt timeout) ]] ; do
  echo -n "."
  sleep 1s
  n=$[$n+1]
done
if [[ n -eq timeout ]];
then
    echo "Could not start ArangoDB. Timeout reached."
    exit 1
fi

echo "ArangoDB is up"

