#!/bin/bash

echo "Starting theme building process..."

# Install
apt install curl -y
curl -sL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y

# Build
npm ci --omit dev
npm run prod

# Cleanup
npm cache clean --force
apt remove curl nodejs -y
rm -rf /app/node_modules/

echo "Theme building process complete. Cleaned up all dependecies to save space."