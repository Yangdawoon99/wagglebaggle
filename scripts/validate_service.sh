#!/bin/bash
echo "Validating the deployment"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [ "$STATUS" -ne 200 ]; then
  echo "Deployment validation failed"
  exit 1
fi
echo "Deployment validation succeeded"
