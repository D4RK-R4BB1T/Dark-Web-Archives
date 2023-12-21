from requests import Session
import json
from time import sleep
from time import time as unix_timestamp

import numpy as np

import sqlite3
from .Constants import _DBFILE, _ADMINS

class User():
	def __init__(self, json_object):
		if "username" in json_object.keys():
			self.username = json_object["username"]
		else:
			self.username = None
		
		if "first_name" in json_object.keys():
			self.first_name = json_object["first_name"]
		else:
			self.first_name = None
		
		if "last_name" in json_object.keys():
			self.last_name = json_object["last_name"]
		else:
			self.last_name = None
		
		self.is_bot = bool(json_object["is_bot"])
		
		if "language_code" in json_object.keys():
			self.language_code = json_object["language_code"]
		else:
			self.language_code = "en"
		
		self.user_id = json_object["id"]

class Update():
	def __init__(self, json_object):
		self.callback = None
		
		# Plain text message
		if "message" in json_object.keys():
			self.data = json_object["message"]
			self.update_type = "message"
			self.from_id = self.data["from"]["id"]
			self.from_user = User(self.data["from"])
			
		# Plain text message - edited
		elif "edited_message" in json_object.keys():
			self.data = json_object["edited_message"]
			self.update_type = "edited_message"
			self.from_id = self.data["from"]["id"]
			self.from_user = User(self.data["from"])
		
		# Group chat message
		elif "my_chat_member" in json_object.keys():
			self.data = json_object["my_chat_member"]
			self.update_type = "channel_message"
			self.from_id = self.data["from"]["id"]
			self.from_user = User(self.data["from"])
		
		# Group chat post (less info than the last data type though, kind of throwaway)
		elif "channel_post" in json_object.keys():
			self.data = json_object["channel_post"]
			self.update_type = "channel_post"
			self.from_id = -1 # not available here
			self.from_user = None
			
		# Result from past InlineKeyboard response
		elif "result" in json_object.keys():
			self.data = json_object["result"]
			self.update_type = "result"
			self.from_id = self.data["from"]["id"]
			self.from_user = User(self.data["from"])
			
			self.callback = self.data["data"]
		# Immediate result from InlineKeyboard response
		elif "callback_query" in json_object.keys():
			self.data = json_object["callback_query"]
			self.update_type = "callback_query"
			self.from_id = self.data["from"]["id"]
			self.from_user = User(self.data["from"])
			
			self.callback = self.data["data"]
		# Results from poll
		elif "poll" in json_object.keys():
			self.data = json_object["poll"]
			self.update_type = "poll"
			#self.from_id = self.data["from"]["id"]
		else:
			raise ValueError("Unrecognized type of update. Here's a full dump of the json_object argument passed:\n"+json.dumps(json_object, indent=4))
		
		self.update_id = json_object["update_id"]
	
	def __str__(self):
		return json.dumps(self.data, indent=4)
	
	# Returns a bool
	def is_command(self):
		if self.update_type == "message":
			if "text" in self.data.keys():
				if self.data["text"].startswith("/"):
					return True
		
		return False
	
	# Returns a tuple
	# tuple[0] is the command string
	# tuple[1] is a list of arguments for the command
	# tuple[1] may be empty if there are no arguments for the command
	#
	# If the message does not contain a command, this function will return (None,None)
	def process_command(self):
		if not self.is_command():
			return (None,None)
		
		msg = self.data["text"][1:].split(" ")
		command = msg[0]
		del msg[0]
		return (command, msg)
	
	# Returns True if the message was saved successfully
	# Returns False if the message failed to save for some reason
	def save(self):
		try:
			jdata = json.dumps(self.data, indent=4)
			update_id = self.update_id
			
			db = sqlite3.connect(_DBFILE)
			cur = db.cursor()
			cur.execute("INSERT INTO tgupdates(update_id, full_json) VALUES (?,?)",(update_id,jdata))
			db.commit()
			return True
		except Exception as e:
			print("Error while saving a Update to the DB: "+str(e))
			return False
	
	# Returns True if this update ID has already been saved
	# Returns False if this update ID hasn't already been saved
	def is_saved(self):
		update_id = self.update_id
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		count = cur.execute("SELECT COUNT(*) FROM tgupdates WHERE update_id="+update_id+";").fetchone()[0]
		if count > 0:
			return True
		else:
			return False

class API():
	def __init__(self, api_key):
		# Session initialization and assignment of API URL
		self.s = Session()
		self.s.headers["User-Agent"] = "TeleBot"
		self.api_key = api_key
		self.base_url = "https://api.telegram.org/bot"+self.api_key
		
		self.proxy = {
			"socks5":"socks5h://localhost:9050",
			"http":"socks5h://localhost:9050",
			"https":"socks5h://localhost:9050"
		}
		
		# Use API URL to get bot username and bot ID
		me = self.getMe()["result"]
		self.bot_id = int(me["id"])
		self.bot_name = me["username"]
		print("Telegram bot initialized - "+self.bot_name+", UID "+str(self.bot_id))
		
		# Create tables if necessary
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		# tgupdates - raw telegram message data
		cur.execute("""create table if not exists tgupdates(
		update_id int primary key,
		full_json text
		)
		""")
		
		# tgusers - employment data relating telegram users to their reddit accounts
		cur.execute("""create table if not exists tgusers(
			user_id int primary key,
			telegram_name text,
			
			deposit_address text,
			balance real
		)
		""")
		
		# gamedata - game state tracking for user-choice games
		cur.execute("""create table if not exists gamedata(
			proof text primary key,
			secret text,
			salt text,
			
			user_id int,
			chat_id int,
			message_id int,
			
			game_type text,
			state text,
			wager real,
			finished int
		)
		""")
		
		# pools - game state tracking for betting pools
		cur.execute("""create table if not exists pools(
			name text primary key,
			description text,
			
			bet_options text,
			pool_data text,
			
			last_update real,
			next_update real
		)
		""")
		
		# payments - track credits and debits
		cur.execute("""create table if not exists payments(
			txid text primary key,
			balance_change real,
			user_id int,
			note text
		)
		""")
		
		# dailyfree - track daily free bonuses
		cur.execute("""create table if not exists dailyfree(
			user_id int primary key,
			last_bonus int not null
		)
		""")
		
		# referrers - track who invited who
		cur.execute("""create table if not exists referrers(
			user_id int primary key,
			referrer_id int
		)
		""")
		
		db.commit()
		
		# Load list of already processed updates
		self.updates_processed = []
		
		res = cur.execute("SELECT update_id FROM tgupdates;")
		for row in res:
			uid = row[0]
			self.updates_processed.append(uid)
	
	def getMe(self):
		r = self.s.get(self.base_url+"/getMe", proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def sendMessage(self, chat_id, text, reply_markup=None, use_html=True, reply_to_message_id=None, disable_notification=False):
		post_data = {
			"chat_id":chat_id,
			"text":text,
			"reply_to_message_id":reply_to_message_id,
			"allow_sending_without_reply":True,
			"disable_notification":disable_notification
		}
		
		if use_html == True:
			post_data["parse_mode"] = "HTML"
		
		if reply_markup != None:
			post_data["reply_markup"] = reply_markup
		
		r = self.s.post(self.base_url+"/sendMessage", json=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def sendAnimation(self, chat_id, file_id, reply_to_message_id=None):
		post_data = {
			"chat_id":chat_id,
			"reply_to_message_id":reply_to_message_id,
			"allow_sending_without_reply":True,
			"animation":file_id
		}
		
		r = self.s.post(self.base_url+"/sendAnimation", json=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def editMessage(self, chat_id, message_id, text, reply_markup=None, use_html=True):
		post_data = {
			"chat_id":chat_id,
			"message_id":message_id,
			"text":text,
		}
		
		if use_html == True:
			post_data["parse_mode"] = "HTML"
		
		if reply_markup != None:
			post_data["reply_markup"] = reply_markup
		
		r = self.s.post(self.base_url+"/editMessageText", json=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def sendPoll(self, chat_id, question, options):
		post_data = {
			"chat_id":chat_id,
			"question":question,
			"options":options
		}
		r = self.s.post(self.base_url+"/sendPoll", json=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def getUserProfilePhotos(self, user_id):
		post_data = {
			"user_id":user_id,
			"limit":100
		}
		r = self.s.post(self.base_url+"/getUserProfilePhotos", json=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def getChat(self, chat_id):
		post_data = {"chat_id":chat_id}
		r = self.s.post(self.base_url+"/getChat", data=post_data, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def setMyCommands(self, commands):
		r = self.s.post(self.base_url+"/setMyCommands", json={"commands":commands}, proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	def getMyCommands(self):
		r = self.s.get(self.base_url+"/getMyCommands", proxies=self.proxy)
		j = json.loads(r.text)
		return j
	
	# Gets updates and returns a list of other Telegram-related objects from the json body of the response
	def getUpdates(self):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		offset = cur.execute("select max(update_id) from tgupdates").fetchone()
		offset = 0 if offset == None else offset[0]
		
		r = self.s.post(self.base_url+"/getUpdates", data={"offset":offset}, proxies=self.proxy)
		j = json.loads(r.text)
		#return j
		
		ret = []
		if j["ok"] == True:
			res = j["result"]
			for obj in res:
				tgu = Update(obj)
				if not tgu.update_id in self.updates_processed:
					self.updates_processed.append(tgu.update_id)
					tgu.save()
					ret.append(tgu)
			return ret
		else:
			raise ValueError("'ok' field wasn't True. Here's a full dump of the response:\n"+json.dumps(j, indent=4))
	
	# update is a Update object with type=="message"
	# If the update object isn't correct, this will return False
	# If the update object is valid and correct, this will reply to the given message
	def reply(self, update, text, reply_markup=None, disable_notification=False):
		if update.update_type == "message":
			chat_id = update.data["chat"]["id"]
		elif update.update_type == "callback_query":
			chat_id = update.data["message"]["chat"]["id"]
		else:
			print("This isn't a message I can reply to.")
			print(json.dumps(update.data, indent=4))
			return False
		
		print("replying to update of type '"+str(update.update_type)+"'")
		
		try:
			reply_id = update.data["message_id"]
		except:
			reply_id = None
		
		return self.sendMessage(chat_id, text, reply_markup, reply_to_message_id=reply_id, disable_notification=disable_notification)
	
	def reply_with_poll(self, update, question, options):
		try:
			assert update.update_type == "message"
		except AssertionError:
			print("This isn't a message I can reply to.")
			return False
		except Exception as e:
			print("ERROR: "+str(e))
			return False
		
		chat_id = update.data["chat"]["id"]
		return self.sendPoll(chat_id, question, options)
