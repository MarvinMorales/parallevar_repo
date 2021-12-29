const WebSocket = require('ws');
const md5 = require('md5');
const port = 8000;
const { Expo } = require('expo-server-sdk');
const axios = require('axios');
wss = new WebSocket.Server({port: process.env.PORT || port},
console.log(`Running on port ${port}`));