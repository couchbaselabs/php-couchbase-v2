#!/bin/sh -ex

PWD=`pwd`
SRC_HOME="/Users/jan/Work/Couchbase/src/Couchbase"

MEMBASE_NUM_VBUCKETS=16
export MEMBASE_NUM_VBUCKETS

cd $SRC_HOME
  cd ns_server
  make dataclean
  ./cluster_run -n 2 > /tmp/cluster.log 2>&1 &
  CLUSTER_RUN_PID=$!
  echo ""
  echo ""
  echo "Cluster Started on pid: $CLUSTER_RUN_PID"
  echo ""
  echo ""
  sleep 20 # yawn
  ./cluster_connect -n 2
  sleep 5
  echo ""
  echo ""
  echo "Cluster Connect Done"
  echo ""
  echo ""
cd ..

cd $PWD # home sweet home

# done
