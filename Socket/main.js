const WebSocket = require('ws');
const md5 = require('md5');
const port = 8000;
const { Expo } = require('expo-server-sdk');
const axios = require('axios');
wss = new WebSocket.Server({port: process.env.PORT || port},
console.log(`Running on port ${port}`));

let CLIENTS = [];
let ROADRUNNERS = [];
let ALLIES = [];
let ADMINS = [];
let PYTHON = [];
let IMAGE = "";

let CLIENTS_LISTENING = [];
let ROADRUNNERS_SPEAKING = [];

getKilometers = (lat1, lon1, lat2, lon2) => {
    rad = (x) => {return x * Math.PI/180;}
    var R = 6378.137; 
    var dLat = rad( lat2 - lat1 );
    var dLong = rad( lon2 - lon1 );
	var a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
	Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * 
	Math.sin(dLong/2) * Math.sin(dLong/2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    var d = R * c;
    return d.toFixed(3);
}

SendPushNotificationCustomer = (pushToken, AllieName, Datas, Title, Body) => {
	let expo = new Expo();
	let messages = [];
	if (!Expo.isExpoPushToken(pushToken)) {
		console.error(`Push token ${pushToken} is not a valid Expo push token`);
		return;
	}
	messages.push({
		to: pushToken,
		sound: 'default',
		title: Title,
		body: Body,
		data: Datas
	});
	let chunks = expo.chunkPushNotifications(messages);
	let tickets = [];
	(async () => {
		for (let chunk of chunks) {
			try {
				let ticketChunk = await expo.sendPushNotificationsAsync(chunk);
				console.log(ticketChunk);
				tickets.push(...ticketChunk);
			} catch (error) {
				console.error(error);
			}
		}
	})();
}

SendPushNotificationRoadrunner = (pushToken, Datas) => {
	let expo = new Expo();
	let messages = [];
	if (!Expo.isExpoPushToken(pushToken)) {
		console.error(`Push token ${pushToken} is not a valid Expo push token`);
		return;
	}
	messages.push({
		to: pushToken,
		sound: 'default',
		title: '¡Hay un nuevo pedido cerca de tu ubicación!',
		body:  `Para ver la infomación acerca del pedido toca aquí ✌️`,
		data: Datas
	});
	let chunks = expo.chunkPushNotifications(messages);
	let tickets = [];
	(async () => {
		for (let chunk of chunks) {
			try {
				let ticketChunk = await expo.sendPushNotificationsAsync(chunk);
				console.log(ticketChunk);
				tickets.push(...ticketChunk);
			} catch (error) {
				console.error(error);
			}
		}
	})();
}

SendDeliveryRequest = (ws) => {
	if (ws.hasOwnProperty('DataAttached')) {
		if (ws.DataAttached.hasOwnProperty('Delivery_Accepted') && ws.DataAttached.Delivery_Accepted === true) {
			const TokenC = ws.DataAttached.PushToken;
			const AllieName = ws.DataAttached.AllieName;
			console.log("Push delivery accepted sent!!!!!");
			let Title = '¡Tu orden ha sido recibida!';
			let Body = `En estos momentos un correcaminos se dirige a ${AllieName} para gestionar tu pedido... 🔥`;
			SendPushNotificationCustomer(TokenC, AllieName, ws.DataAttached, Title, Body);
		} else if (ws.DataAttached.hasOwnProperty('Delivery_Accepted') && ws.DataAttached.Delivery_Accepted === false) {
				if (ws.DataAttached.hasOwnProperty('CustomerLocation')) {
					if (ROADRUNNERS.length !== 0) {
						let C_lat = ws.DataAttached.CustomerLocation.Latitude;
						let C_lon  = ws.DataAttached.CustomerLocation.Longitude;
						ROADRUNNERS.map(R => {
							let R_lat = R.DataAttached.RoadrunnerLocation.Latitude;
							let R_lon = R.DataAttached.RoadrunnerLocation.Longitude;
							let TokenR = R.DataAttached.PushToken;
							let kilometers = parseFloat(getKilometers(C_lat, C_lon, R_lat, R_lon));
							if (kilometers <= 2) {
								ws.DataAttached['RoadrunnerData'] = R.DataAttached;
								SendPushNotificationRoadrunner(TokenR, ws.DataAttached);
							}
						});
					}
				}
		}
	}
}

wss.on("connection", ws => {
	console.log(`There is a new client connected!`);
	ws.onmessage = (e) => {
		let res = JSON.parse(e.data);
		//console.log(res);
		if (res[0].hasOwnProperty('Type')) {
			switch (res[0].Type) {

				case "REQUEST_ORDER_CONFIRMATION":
					ws['WebsocketID'] = res[0].PushToken;
					ws['DataAttached'] = res[0];
					res[0]['OrderID'] = md5(res[0].PushToken);
					ws['Counts'] = 1;
					ws.DataAttached['Delivery_Accepted'] = false;
					ws.DataAttached['Order_Accepted'] = false;
					
					if (CLIENTS.length !== 0) {
					   for (let s in CLIENTS) { 
						  if (CLIENTS[s].WebsocketID !== res[0].PushToken) {
							CLIENTS.push(ws);
						  }
						}
					} else if (CLIENTS.length === 0) { 
						CLIENTS.push(ws);
					}

					ALLIES.map(Allie => { if (Allie.AllieName === res[0].AllieName) { Allie.send(JSON.stringify(res[0])) } });
				break;

				case "REQUEST_ROADRUNNER":
					let Client = {};
					CLIENTS.map(item => {
						if (item.DataAttached.PushToken === res[0].PushToken) {
							Client = item;
							item.send(JSON.stringify([res[0]]));
						}
					});
					let Title = '¡Tu orden ha sido recibida!';
					let Body = `En estos momentos ${Client.DataAttached.AllieName} ha empezado a preparar tu orden, te notificaremos 
					cuando esté lista y un correcaminos vaya a retirarla! 🔥`;
					SendPushNotificationCustomer(Client.DataAttached.PushToken, res[0].AllieTable, res[0], Title, Body);
					setInterval(() => {
							if (ROADRUNNERS.length !== 0) {
								CLIENTS.map(client => {
								  if (client.PushToken === res[0].PushToken && 
									client.DataAttached.Delivery_Accepted === false && client.Counts > 1) {
									  let C_lat = client.DataAttached.CustomerLocation.Latitude;
									  let C_lon  = client.DataAttached.CustomerLocation.Longitude;
									  if (ROADRUNNERS.length !== 0) {
										 ROADRUNNERS.map(R => {
										  console.log("Checking Mapping");
										  let R_lat = R.DataAttached.RoadrunnerLocation.Latitude;
										  let R_lon = R.DataAttached.RoadrunnerLocation.Longitude;
										  let kilometers = parseFloat(getKilometers(C_lat, C_lon, R_lat, R_lon));
										  if (kilometers <= 2) {
											  client.DataAttached['RoadrunnerData'] = R.DataAttached;
											  console.log("Checking Kilometers")
											  SendPushNotificationRoadrunner(R.DataAttached.PushToken, client.DataAttached);
											  console.log(`Kilometros: ${kilometers}Km`);
										  }
										});
									  } client.Counts += 1;
								  } else {
									 res['Delivery_Accepted'] = false;
								  }
								});
								console.log("Nuevo While");
							  }
					}, 180000);
				break;
	
				case "LOOKING_FOR_CLIENTS":
					ws['WebsocketID'] = res[0].PushToken;
					ws['DataAttached'] = res[0];
					if (ROADRUNNERS.length !== 0) {
						for (let s in ROADRUNNERS) { 
						   if (ROADRUNNERS[s].WebsocketID !== res[0].PushToken) {	
							 ROADRUNNERS.push(ws);
						   }
						 }
					 } else if (ROADRUNNERS.length === 0) { 
						 ROADRUNNERS.push(ws);
					 }
					 break;
	
				case "LOOKING_FOR_ORDERS":
					ws['WebsocketID'] = res[0].Token;
					ws['AllieName'] = res[0].AllieName;
					if (ALLIES.length !== 0) {
						for (let s in ALLIES) { 
						   if (ALLIES[s].WebsocketID !== res[0].Token) {
							 ALLIES.push(ws);
						   }
						 }
					 } else if (ALLIES.length === 0) { 
						 ALLIES.push(ws);
					 } 
				break;
	
				case "DELIVERY_ACCEPTED":
					if (res[0].hasOwnProperty('Delivery_Accepted') && res[0].Delivery_Accepted === true) {
						CLIENTS.map(item => {
							if (item.DataAttached.PushToken === res[0].PushToken) {
								item.DataAttached.Delivery_Accepted = true;
							}
						});
						const TokenC = res[0].PushToken;
						const AllieName = res[0].AllieName;
						CLIENTS.map(Client => {
							console.log(Client.DataAttached.CustomerData.CustomerOrderID + "=" + res[0].CustomerData.CustomerOrderID);
							if (Client.DataAttached.CustomerData.CustomerOrderID === res[0].CustomerData.CustomerOrderID) {
								OrderAcc = Client.DataAttached.CustomerData.CustomerOrderID;
								ALLIES.map(allie => {
									if (allie.WebsocketID + "=" + res[0].AllieWebsocketID) {
										allie.send(JSON.stringify(res[0]));
										let Title = '¡Tu orden ha sido recibida!';
										let Body = `En estos momentos un correcaminos se dirige a ${AllieName} para gestionar tu pedido... 🔥`;
										SendPushNotificationCustomer(TokenC, AllieName, res[0], Title, Body);
									}
								});
							}
						});
					}
				break;
				
				case "LISTEN_ORDER_STATUS":
					if (CLIENTS_LISTENING.length !== 0) {
						let Client = CLIENTS_LISTENING.find(element => element.FinalOrder.OrderID !== res[0].OrderID);
						if (Client !== undefined) { 
							CLIENTS_LISTENING.push(ws);
							obj = {Type: 'PREPARING_ORDER', OrderID: res[0].OrderID}
							ws.send(JSON.stringify(obj));
							let NewArr = CLIENTS.filter(client => client.DataAttached.CustomerData.CustomerOrderID === res[0].OrderID);
						}
					} else {
						ws['FinalOrder'] = res[0];
						CLIENTS_LISTENING.push(ws);
						obj = {Type: 'PREPARING_ORDER', OrderID: res[0].OrderID}
						ws.send(JSON.stringify(obj));
						let NewArr = CLIENTS.filter(client => client.DataAttached.CustomerData.CustomerOrderID === res[0].OrderID);
					}
				break;
					
				case "SEND_STATUS_FOR_CLIENT":
					ROADRUNNERS_SPEAKING.push(res[0]);
				break;

				case "LOOK_FOR_EXTERNAL_DELIVERY":
					let order = res[0].order;
					
				break;
	
				default:
					ws.send(JSON.stringify({ Response: "No appropriate 'Case' found" }));
			}
		} else {
			ws.send(JSON.stringify({ Access: false, Reason: "There's no 'Type' Property" }));
		}

		console.log("Clientes:", CLIENTS.length);
		console.log("Correcaminos:", ROADRUNNERS.length);
		console.log("Aliados:", ALLIES.length);
		setTimeout(() => {
			SendDeliveryRequest(ws);
		}, 3000);
	};

	ws.on("close", (code) => {
		console.log("There was a close")
		console.log("There was an Automatic Close:", code)
		ws.send(JSON.stringify({CloseCode: code}));
		CLIENTS.map(item => {
			if (item === ws) { CLIENTS.splice(CLIENTS.indexOf(ws), 1) }
		});

		ALLIES.map(item => {
			if (item === ws) { ALLIES.splice(ALLIES.indexOf(ws), 1) }
		});

		ROADRUNNERS.map(item => {
			if (item === ws) { ROADRUNNERS.splice(ROADRUNNERS.indexOf(ws), 1) }
		});
		console.log("Clientes:", CLIENTS.length);
		console.log("Correcaminos:", ROADRUNNERS.length);
		console.log("Aliados:", ALLIES.length);
	});

	ws.on("error", () => {
		CLIENTS.map(item => {
			if (item === ws) { CLIENTS.splice(CLIENTS.indexOf(ws), 1) }
		});

		ALLIES.map(item => {
			if (item === ws) { ALLIES.splice(ALLIES.indexOf(ws), 1) }
		});

		ROADRUNNERS.map(item => {
			if (item === ws) { ROADRUNNERS.splice(ROADRUNNERS.indexOf(ws), 1) }
		});
		console.log("Clientes:", CLIENTS.length);
		console.log("Correcaminos:", ROADRUNNERS.length);
		console.log("Aliados:", ALLIES.length);
	});
});

