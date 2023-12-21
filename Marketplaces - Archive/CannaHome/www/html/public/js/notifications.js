function htmlToElement(html) {
	var template = document.createElement('template');
	template.innerHTML = html;
	return template.content.firstChild;
}

function spawnNotification(theTitle, theBody) {
	if (Notification.permission === "granted") {
		var options = {body: theBody}
		var n = new Notification(theTitle,options);
	}
}

setInterval(
	function (){
		if (document.getElementById('toggle-live_updates').checked){
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.open("GET", '/api/fetch_user_notifications/');
			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == XMLHttpRequest.DONE) {
					if(xmlhttp.status == 200){
						var response = JSON.parse(xmlhttp.responseText);
			
						document.title = document.title.replace(/(?:\(\d+\)\s)?(.+)/g, (response.messages > 0 ? '(' + response.messages + ') $1' : '$1'));
						
						var allNotifications = response.notifications;
			
						if (allNotifications.length > 1){
							if (e = document.querySelector('#notifications-column > strong'))
								e.remove();
				
							var hasSound = false;
							var preservedNotifications = [];
							var newNotifications = [];
							allNotifications.forEach(
								function(notification, index){
									if (document.getElementById(notification.ID) !== null)
										preservedNotifications.push(notification.ID);
									else
										newNotifications.push(notification);
								}
							);
			
							var existingNotifications = document.querySelectorAll("#dashboard-notfs > li");
							Array.prototype.forEach.call(
								existingNotifications,
								function(node){
									if(
										node.id &&
										preservedNotifications.indexOf(node.id) == -1
									)
										node.remove();
								}
							);
			
							var firstNotification = document.querySelector('#dashboard-notfs > li:first-child');
			
							if (newNotifications.length > 0){
								newNotifications.forEach(
									function(notification){
										var newNotificationContainer = document.createElement("li");
							
										hasSound = notification.Sound ? true : hasSound;
							
										newNotificationContainer.id = notification.ID;
										newNotificationContainer.className = notification.Design.Color
					
										var newNotification = htmlToElement((notification.Dismiss ? '<a class="close" href="' + notification.Dismiss + '">&times;</a>' : '') + '<' + (notification.Anchor ? 'a  href="' + notification.Anchor + '" target="' + notification.Target + '"' : 'div') + '>' + (notification.Design.Icon ? '<i class="' + notification.Design.Icon + '"></i>' : '') + '<div><div><span>' + notification.Content + '</span></div></div></' + (notification.Anchor ? 'a' : 'div') + '>');
					
										newNotificationContainer.appendChild(newNotification);
					
										if(firstNotification)
											firstNotification.parentNode.insertBefore(newNotificationContainer, firstNotification);
										else
											document.querySelector('#dashboard-notfs').appendChild(newNotificationContainer);
									}
								);
					
								if(
									document.getElementById('toggle-notifications').checked &&
									hasSound
								){
									var notificationTitle = newNotifications.length + ' new notifications on CGMC';
									var notificationBody = '';
									if (newNotifications.length == 1){
										var notificationTitle = 'New notification on CGMC';
										var notificationBody = newNotifications[0].Content.replace(/<\/?[^>]+(>|$)/g, "");
									}
									
									spawnNotification(notificationTitle, notificationBody);
								}
							}
						} else {
							document.getElementById('dashboard-notfs').remove();	
				
							e = createElement('strong');
							e.textContent = 'No New Notifications';
							document.getElementById('notifications-column').appendChild(e);
						}
					}
				}
			}
			xmlhttp.send();
		}
	},
	60000
);

document.addEventListener(
	"DOMContentLoaded",
	function (event) {
		document.querySelector("#notifications-column h3 > div").style.display = 'block';
		
		var liveBtn = document.querySelector('[for="toggle-live_updates"]');
		var notificationsBtn = document.querySelector('[for="toggle-notifications"]');

		var toggleLive = document.getElementById('toggle-live_updates');
		var toggleNotifications = document.getElementById('toggle-notifications');

		//var toggleNotificationsHint = document.querySelector('[for="toggle-notifications"] .hint');

		toggleLive.addEventListener(
			'change',
			function (event) {
				if (toggleLive.checked) {
					var liveBtnColor = 'red';
					var liveBtnText = 'Disable Live Updates';
			
					var notificationsBtnColor = toggleNotifications.checked ? 'red' : 'green';
					//var notificationsBtnText = toggleNotifications.checked ? 'Disable Notifications' : 'Enable Notifications';
			
					toggleNotifications.removeAttribute('disabled');
				} else {
					var liveBtnColor = 'green';
					var liveBtnText = 'Enable Live Updates';
			
					var notificationsBtnColor = 'disabled';
					//var notificationsBtnText = false;
			
					toggleNotifications.setAttribute('disabled', '');
				}
		
				liveBtn.className = 'btn ' + liveBtnColor;
				liveBtn.innerHTML = '<i class="icon-rss"></i>' + liveBtnText;
		
				notificationsBtn.className = 'btn xs ' + notificationsBtnColor;
				
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.open("GET", '/api/update_user_prefs/LiveUpdate/' + (toggleLive.checked ? '1' : '0') + '/');
				xmlhttp.send();
			}
		);

		toggleNotifications.addEventListener(
			'change',
			function (event) {
				console.log('hi');
				if (toggleLive.checked) {
					if (toggleNotifications.checked) {
						if (Notification.permission !== 'denied') {
							Notification.requestPermission(
								function (permission) {
									if (permission === "granted")
										notificationsBtn.className = 'btn xs red';
									else
										toggleNotifications.checked = false;
								}
							);
						} else
							toggleNotifications.checked = false;
					} else 
						notificationsBtn.className = 'btn xs green';
				} else
					toggleNotifications.checked = false;
			}
		);
	}
);
