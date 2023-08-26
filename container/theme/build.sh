#!/bin/bash

echo "Starting theme building process..."

apt install curl -y
curl -sL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
npm ci --omit dev

npm run prod