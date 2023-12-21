from .Constants import _DBFILE

from Crypto.Random import get_random_bytes
from Crypto.Hash import SHA256
import numpy as np

import sqlite3
import json
from time import time as unix_timestamp

class GameBase:
	def __init__(self):
		return None
	
	
	# Creates the salted & hashed proof for a given game_secret
	# game_secret is the deterministic status of the game; e.g. in a coin flip game, game_secret would be either "heads" or "tails"
	# returns a dictionary object with keys "proof", "salt", and "unhashed"
	def create_proof(self, game_secret):
		if type(game_secret) != str:
			raise ValueError("game_secret needs to be a string or unicode object.")
		
		salt = SHA256.new(get_random_bytes(64)).hexdigest()[0:12]
		
		unhashed = (game_secret+":"+salt).encode("utf-8")
		#hashed = SHA256.new(unhashed).hexdigest()
		hashed = SHA256.new(unhashed).hexdigest()[0:32]
		
		return {"proof":hashed,"salt":salt,"unhashed":unhashed}
	
	# debit
	#   user: "User" object (from GamblingBot.Telegram)
	#   value: float, the amount to deduct from their account balance
	#
	# Debits a user's account
	def debit(self, user, value, note=""):
		
		# allow optional sending just the UID instead of full User object
		if type(user) == int:
			uid = user
		else:
			uid = user.user_id
		
		value = (-1.*value) if value>0 else value
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		cur.execute("insert into payments values (?,?,?,?)",(str(unix_timestamp()),value,uid,note))
		db.commit()
	
	# credit
	#   user: "User" object (from GamblingBot.Telegram)
	#   value: float, the amount to deduct from their account balance
	#
	# Credits a user's account
	def credit(self, user, value, note=""):
		
		# allow optional sending just the UID instead of full User object
		if type(user) == int:
			uid = user
		else:
			uid = user.user_id
		
		if value > 0:
			db = sqlite3.connect(_DBFILE)
			cur = db.cursor()
			cur.execute("insert into payments values (?,?,?,?)",(str(unix_timestamp()),value,uid,note))
			db.commit()
	
	# get_balance
	#   user: "User" object (from GamblingBot.Telegram)
	#
	# Returns the user's current balance as a floating point number, rounded to 8 digits accuracy
	def get_balance(self, user):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		# allow optional sending just the UID instead of full User object
		if type(user) == int:
			uid = user
		else:
			uid = user.user_id
		
		res = cur.execute("select sum(balance_change) from payments where user_id=?",(uid,)).fetchone()
		if res == None:
			return 0.0
		else:
			if res[0] == None:
				return 0.0
			else:
				return round(res[0],8)

class PoolBase(GameBase):
	def __init__(self):
		GameBase.__init__(self)
		
		self.default_pool_data = json.dumps({})
		
		self.pool_interval = 1000000000. # Interval (in seconds) which pool updates
		self.pool_name = "PoolBase" # Pool name, should be overwritten in child functions
		return None
	
	# pool_exists
	#   pool_name: string, the name of a pool
	#
	# Return value: boolean indicating if pool exists or not
	def pool_exists(self, pool_name):
		if type(pool_name) != str:
			print(type(pool_name))
			return False
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		return bool(cur.execute("select count(*) from pools where name=?",(pool_name,)).fetchone()[0])
	
	# join_pool
	#   user: "User" object (from GamblingBot.Telegram)
	#   pool_name: string, the name of the pool to join
	#   wager: float, the amount to deduct from their account balance
	#
	# Return value: tuple
	# tuple[0]: boolean indicating if pool was joined successfully or not
	# tuple[1]: status message
	def join_pool(self, user, pool_name, wager, bet_option):
		try:
			wager = float(wager)
			assert wager > 0
		except:
			return False, "Invalid bet value."
		
		try:
			assert type(pool_name) == str
			assert self.pool_exists(pool_name) == True
		except:
			return False, "Invalid pool."
		
		try:
			uid = user.user_id
			balance = self.get_balance(user)
			assert balance >= wager
		except:
			return False, "You do not have enough money to make this bet."
		
		try:
			assert bet_option in self.get_options(pool_name)
		except Exception as e:
			return False, "Invalid bet option."
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		pool_data = json.loads(cur.execute("select pool_data from pools where name=? limit 1",(pool_name,)).fetchone()[0])
		if uid in pool_data.keys():
			pool_data[bet_option][uid] += wager
		else:
			pool_data[bet_option][uid] = wager
		
		self.debit(user, wager, "put "+str(wager)+" LTC into pool '"+pool_name+"'")
		cur.execute("update pools set pool_data=? where name=? limit 1",(json.dumps(pool_data),pool_name))
		db.commit()
		
		return True, "Pool joined successfully."
	
	# rewrite_times
	#    Takes no arguments. Just updates the DB based on self.pool_name and self.pool_interval
	def rewrite_times(self):
		now = unix_timestamp()
		next_update = now+self.pool_interval
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		cur.execute("update pools set last_update=?,next_update=? where name=? limit 1",(now,next_update,self.pool_name))
		db.commit()
	
	# get_pools
	#    Takes no arguments.
	#
	# Return value: list. each item in the list is a dict, with keys 'name', 'description', 'last_update', and 'next_update'
	# The next_update and last_updates are expressed as number of seconds between current time and the expressed time.
	def get_pools(self):
		returnval = []
		now = unix_timestamp()
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		res = cur.execute("select name,description,last_update,next_update from pools order by name")
		for pname, pdesc, plast, pnext in res:
			returnval.append({
				"name":pname,
				"description":pdesc,
				"last_update":int(now-plast),
				"next_update":int(pnext-now)
			})
		
		return returnval
	
	# get_pool_data
	# Returns a list of current betters in a given pool
	#   pool_name: string, the name of a pool
	#
	# Return value: list of dicts. each dict has the key username, option, and value.
	def get_pool_data(self, pool_name):
		if not self.pool_exists(pool_name):
			return []
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		res = cur.execute("select pool_data from pools where name=? limit 1",(pool_name,)).fetchone()[0]
		try:
			pool_data = json.loads(res)
		except Exception as e:
			print("[get_options] ERROR: "+str(e))
			return []
		
		returnvalue = []
		bet_options = self.get_options(pool_name)
		for bet_option in bet_options:
			for k,v in pool_data[bet_option].items():
				username = str(cur.execute("select telegram_name from tgusers where user_id=? limit 1",(int(k),)).fetchone()[0])
				returnvalue.append({
					"username":username,
					"option":bet_option,
					"value":v
				})
		return returnvalue
	
	# get_options
	#   pool_name: string, the name of a pool
	#
	# Return value: python list object of valid bet strings
	def get_options(self, pool_name):
		if not self.pool_exists(pool_name):
			return []
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		res = cur.execute("select bet_options from pools where name=? limit 1",(pool_name,)).fetchone()[0]
		try:
			return json.loads(res)
		except Exception as e:
			print("[get_options] ERROR: "+str(e))
			return []
	
	# update
	# Placeholder function. Must return a string in child classes.
	# This function should also rewrite the 
	def update(self):
		return "[DEBUG] This is a placeholder function. Child classes should overwrite this function."

class FlipPool(PoolBase):
	def __init__(self):
		PoolBase.__init__(self)
		
		self.default_pool_data = {"heads":{},"tails":{}}
		
		self.pool_interval = 60. # Interval (in seconds) which pool updates
		self.pool_name = "flip" # Pool name, should be overwritten in child functions
		
		if not self.pool_exists(self.pool_name):
			now = unix_timestamp()
			db = sqlite3.connect(_DBFILE)
			cur = db.cursor()
			params = [
				self.pool_name,
				"Coin flip betting pool",
				json.dumps(["heads","tails"]),
				json.dumps(self.default_pool_data),
				now,
				now+self.pool_interval
			]
			cur.execute("insert into pools values (?,?,?,?,?,?)",params)
			db.commit()
		
		return None
	
	def update(self):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		now = unix_timestamp()
		next_update_due,pool_data = cur.execute("select next_update,pool_data from pools where name=? limit 1",(self.pool_name,)).fetchone()
		
		if now >= next_update_due:
			self.rewrite_times()
			cur.execute("update pools set pool_data=? where name=? limit 1",(json.dumps(self.default_pool_data),self.pool_name))
			db.commit()
			
			opt = (float(ord(get_random_bytes(1)))/255.)
			choice = "heads" if opt>=0.5 else "tails"
			
			# read pool data
			pool_data = json.loads(pool_data)
			
			# if there are no players, just return None
			heads_players = pool_data["heads"]
			tails_players = pool_data["tails"]
			
			if len(heads_players) == 0 and len(tails_players) == 0:
				return None
			
			# if there are players in only 1 side of the coin, refund that player and send a notice indicating it
			if len(heads_players) == 0 and len(tails_players) > 0:
				for k,v in tails_players.items():
					self.credit(int(k),v,"flip pool refund - everyone chose tails")
				return str(len(tails_players))+" players bet on tails, but nobody bet on heads. All players have been refunded.\n\n[debug] pool_data="+json.dumps(pool_data)
			elif len(heads_players) > 0 and len(tails_players) == 0:
				for k,v in heads_players.items():
					self.credit(int(k),v,"flip pool refund - everyone chose heads")
				return str(len(heads_players))+" players bet on heads, but nobody bet on tails. All players have been refunded.\n\n[debug] pool_data="+json.dumps(pool_data)
			
			# if the betting is set to go forward, with enough players on each team
			# calculate the total
			tails_total = 0.0
			heads_total = 0.0
			for k,v in tails_players.items():
				tails_total += v
			for k,v in heads_players.items():
				heads_total += v
			
			# apply house fee
			# determine proportionate payout values
			#
			# example: 2 LTC on heads, 1 LTC on tails
			# heads wins
			# total value is 3 LTC
			# 3/2 = 1.5x multiplier (heads / tails)
			
			if choice == "heads":
				multiplier = ((heads_total+tails_total)/heads_total)*0.99
				winner_dict = heads_players
			else:
				multiplier = ((heads_total+tails_total)/tails_total)*0.99
				winner_dict = tails_players
			
			# apply the credits
			response_string = choice+" won! The pool total was "+str(round(tails_total+heads_total,8))+" LTC, and "+choice+" players got a "+str(round(multiplier,5))+"x multiplier on their bets. Here are the winners.\n\n<code>"
			for k,v in winner_dict.items():
				payout = round(v*multiplier,8)
				username = str(cur.execute("select telegram_name from tgusers where user_id=? limit 1",(int(k),)).fetchone()[0])
				response_string += "@"+username+" - bet "+str(round(v,8))+" LTC, got "+str(payout)+" LTC\n"
				
				self.credit(int(k), payout, "flip pool - "+choice+" won")
			
			response_string += "</code>"
			
			return response_string

class PricePoolBTC(PoolBase):
	def __init__(self):
		PoolBase.__init__(self)
		
		self.default_pool_data = {"rise":{},"fall":{}}
		
		self.pool_interval = 60.*30. # Interval (in seconds) which pool updates
		self.pool_name = "btcprice" # Pool name, should be overwritten in child functions
		
		if not self.pool_exists(self.pool_name):
			now = unix_timestamp()
			db = sqlite3.connect(_DBFILE)
			cur = db.cursor()
			params = [
				self.pool_name,
				"BTC price gambling pool. Will the prise rise or fall?",
				json.dumps(["rise","fall"]),
				json.dumps(self.default_pool_data),
				now,
				now+self.pool_interval
			]
			cur.execute("insert into pools values (?,?,?,?,?,?)",params)
			db.commit()
		
		return None
	
	def update(self):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		now = unix_timestamp()
		next_update_due,pool_data = cur.execute("select next_update,pool_data from pools where name=? limit 1",(self.pool_name,)).fetchone()
		
		if now >= next_update_due:
			self.rewrite_times()
			cur.execute("update pools set pool_data=? where name=? limit 1",(json.dumps(self.default_pool_data),self.pool_name))
			db.commit()
			
			opt = (float(ord(get_random_bytes(1)))/255.)
			choice = "heads" if opt>=0.5 else "tails"
			
			# read pool data
			pool_data = json.loads(pool_data)
			
			# if there are no players, just return None
			rise_players = pool_data["rise"]
			fall_players = pool_data["fall"]
			
			if len(rise_players) == 0 and len(fall_players) == 0:
				return None
			
			# if there are players in only 1 side of the coin, refund that player and send a notice indicating it
			if len(rise_players) == 0 and len(fall_players) > 0:
				for k,v in fall_players.items():
					self.credit(int(k),v,"btc price pool refund - everyone chose fall")
				return str(len(fall_players))+" players bet on fall, but nobody bet on rise. All players have been refunded."
			elif len(rise_players) > 0 and len(fall_players) == 0:
				for k,v in rise_players.items():
					self.credit(int(k),v,"btc price pool refund - everyone chose rise")
				return str(len(rise_players))+" players bet on rise, but nobody bet on fall. All players have been refunded."
			
			# if the betting is set to go forward, with enough players on each team
			# calculate the total
			fall_total = 0.0
			rise_total = 0.0
			for k,v in fall_players.items():
				fall_total += v
			for k,v in rise_players.items():
				rise_total += v
			
			# apply house fee
			# determine proportionate payout values
			#
			# example: 2 LTC on rise, 1 LTC on fall
			# rise wins
			# total value is 3 LTC
			# 3/2 = 1.5x multiplier (rise / fall)
			
			if choice == "rise":
				multiplier = ((rise_total+fall_total)/rise_total)*0.99
				winner_dict = rise_players
			else:
				multiplier = ((rise_total+fall_total)/fall_total)*0.99
				winner_dict = fall_players
			
			# apply the credits
			response_string = "The price of BTC has "+choice+"en! The pool total was "+str(round(fall_total+rise_total,8))+" LTC, and '"+choice+"' players got a "+str(round(multiplier,5))+"x multiplier on their bets. Here are the winners.\n\n<code>"
			for k,v in winner_dict.items():
				payout = round(v*multiplier,8)
				username = str(cur.execute("select telegram_name from tgusers where user_id=? limit 1",(int(k),)).fetchone()[0])
				response_string += "@"+username+" - bet "+str(round(v,8))+" LTC, got "+str(payout)+" LTC\n"
				
				self.credit(int(k), payout, "flip pool - "+choice+" won")
			
			response_string += "</code>"
			
			return response_string

class Minesweeper(GameBase):
	def __init__(self):
		GameBase.__init__(self)
		
	# kind of a stupid way to do this, but it works
	def random_secret(self):
		return_value = ""
		for i in range(5):
			random_value = 3
			while random_value == 3:
				random_value = int(np.floor(((float(ord(get_random_bytes(1)))/255.)*2.9999999999999999)))
			return_value += ["L","C","R"][random_value]
		
		return return_value
	
	def create_game(self, update, wager):
		# choose the winning value and create a proof hash
		winning_choice = self.random_secret()
		proofdata = self.create_proof(winning_choice)
		
		# write this game's data into the database
		params = (
			proofdata["proof"],
			winning_choice,
			proofdata["salt"],
			
			update.from_id,
			update.data["chat"]["id"],
			update.data["message_id"],
			
			"minesweeper",
			'{"level": 0}',
			wager,
			0
		)
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		cur.execute("insert into gamedata values (?,?,?, ?,?,?, ?,?,?,?)", params)
		db.commit()
		
		# send it out to the chat!
		keyboard_markup = {"inline_keyboard":[
			[
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				}
			],
			[
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				}
			],
			[
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				}
			],
			[
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				}
			],
			[
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				},
				{
					"text":"\ud83d\udd12",
					"callback_data":"donothing"
				}
			],
			
			[
				{
					"text":"Left",
					"callback_data":"mL-"+proofdata["proof"]
				},
				{
					"text":"Center",
					"callback_data":"mC-"+proofdata["proof"]
				},
				{
					"text":"Right",
					"callback_data":"mR-"+proofdata["proof"]
				}
			],
			
			[
				{
					"text":"Cash out early",
					"callback_data":"mF-"+proofdata["proof"]
				}
			]
		]}
		response_text = "Your wager of "+str(wager)+" LTC is ready. Choose Left, Center, or Right and see if you hit a mine.\n\nYour proof hash is "+proofdata["proof"]+". The input that creates this proof will be revealed after your game is finished.\n"
		return response_text, keyboard_markup
	
	# Updates the state when a callback message is received from the button press
	def update_state(self, update):
		'''
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
		'''
		chunks = update.callback.split("-")
		game_choice = chunks[0]
		game_proof = chunks[1]
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		gamedata = cur.execute("select secret,salt,wager,state from gamedata where finished=0 and user_id=? and chat_id=? and proof=? limit 1",(update.from_id, update.data["message"]["chat"]["id"], game_proof)).fetchone()
		if gamedata == None:
			return False,0.0,None,None
		
		game_secret, game_salt, wager, game_state = gamedata
		game_state = json.loads(game_state)
		
		# process when users press the button to cash out early
		if game_choice.startswith("mF") or game_state["level"]==5:
			cur.execute("update gamedata set finished=1 where proof=? limit 1",(game_proof,))
			db.commit()
			
			multiplier = round((1./((2./3.)**game_state["level"]))*0.95, 5)
			potential_payout = round(multiplier * wager, 8)
			
			response_text = "You cashed out on level "+str(game_state["level"])+" and won a multiplier of "+str(multiplier)+"x on your initial bet of "+str(wager)+" LTC. Your reward is <b><u>"+str(potential_payout)+" LTC!!!</u></b>"
			
			return True, potential_payout, response_text, None
		
		# process when users press the button to choose the location of the next mine
		elif game_choice in ["mL","mC","mR"]:
			game_choice = game_choice[1] # cut off the first character
			
			
			
			# get the mine's location based on the 'level' key in the game_state dict
			mine_location = game_secret[game_state["level"]]
			
			# increment the level in the game state
			game_state["level"] += 1
			print("game_state['level']",game_state["level"])
			
			# determine the current multiplier based on the level
			multiplier = round((1./((2./3.)**game_state["level"]))*0.95, 5)
			potential_payout = round(multiplier * wager, 8)
			
			# if the mine's location is the same as user's chosen location, they lost
			if mine_location == game_choice:
				# set up response text
				conversion_dictionary = {
					"L":"left",
					"C":"center",
					"R":"right"
				}
				game_choice_plain = conversion_dictionary[game_choice]
				response_text = "You exploded! You chose the "+game_choice_plain+" square and there was a mine there. Better luck next time!\n\nProof hash: "+game_proof+"\nGame proof formula: sha256("+game_secret+":"+game_salt+")"
				
				cur.execute("update gamedata set finished=1 where proof=? limit 1;",(game_proof,))
				db.commit()
			
				# finalize the game and return
				return True, 0.0, response_text, None
			
			# if they chose safely, update the game state and level variable and other things, then show them the updated game board
			else:
				
				#If the level is now 6, the user has won all levels and should be paid out
				if game_state["level"] == 6:
					cur.execute("update gamedata set finished=1 where proof=? limit 1",(game_proof,))
					db.commit()
					
					response_text = "You cleared the whole mine field! You won a multiplier of "+str(multiplier)+"x on your initial bet of "+str(wager)+" LTC. Your reward is <b><u>"+str(potential_payout)+" LTC!!!</u></b>"
					return True, potential_payout, response_text, None
				
				# set up response text
				response_text = "Wager: "+str(wager)+" LTC\nMultiplier: "+str(multiplier)+"x\nPotential payout: "+str(potential_payout)+" LTC\n\nChoose Left, Center, or Right and see if you hit a mine.\n\nYour proof hash is "+game_proof+". The input that creates this proof will be revealed after your game is finished.\n"
				
				# re-render the game menu
				keyboard = [
					[
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						}
					],
					[
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						}
					],
					[
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						}
					],
					[
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						}
					],
					[
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						},
						{
							"text":"\ud83d\udd12",
							"callback_data":"donothing"
						}
					],
					
					[
						{
							"text":"Left",
							"callback_data":"mL-"+game_proof
						},
						{
							"text":"Center",
							"callback_data":"mC-"+game_proof
						},
						{
							"text":"Right",
							"callback_data":"mR-"+game_proof
						}
					],
					
					[
						{
							"text":"Cash out early",
							"callback_data":"mF-"+game_proof
						}
					]
				]
				
				# for each row in the keyboard...
				for row_index in range(5):
					# if this level has already been passed, display the truth
					if game_state["level"] > row_index:
						for col_index in range(3):
							coldata = ["L","C","R"]
							if coldata[col_index] == game_secret[row_index]:
								keyboard[4-row_index][col_index] = {"text":"\ud83d\udca3","callback_data":"donothing"}
							else:
								keyboard[4-row_index][col_index] = {"text":"\ud83e\udd1e","callback_data":"donothing"}
					
					# if this level has not yet been passed, do nothing, since the Xs are already present
				
				keyboard_markup = {"inline_keyboard":keyboard}
				
				# update the game state in the DB
				game_state = json.dumps(game_state)
				cur.execute("update gamedata set state=? where proof=? limit 1;",(game_state,game_proof))
				db.commit()
				
				return True, 0.0, response_text, keyboard_markup
		
		return False, 0.0, "", None


class Dice(GameBase):
	def __init__(self):
		GameBase.__init__(self)
		
	# kind of a stupid way to do this, but it works
	def random_secret(self):
		random_value = 6
		while random_value == 6:
			random_value = int(np.floor(((float(ord(get_random_bytes(1)))/255.)*5.9999999999999999)))
		return "d"+str(random_value+1)
	
	def create_game(self, update, wager):
		# choose the winning value and create a proof hash
		winning_choice = self.random_secret()
		proofdata = self.create_proof(winning_choice)
		
		# write this game's data into the database
		params = (
			proofdata["proof"],
			winning_choice,
			proofdata["salt"],
			
			update.from_id,
			update.data["chat"]["id"],
			update.data["message_id"],
			
			"dice",
			"[]",
			wager,
			0
		)
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		cur.execute("insert into gamedata values (?,?,?, ?,?,?, ?,?,?,?)", params)
		db.commit()
		
		# send it out to the chat!
		keyboard_markup = {"inline_keyboard":[
			[
				{
					"text":"#1",
					"callback_data":"d1-"+proofdata["proof"]
				},
				{
					"text":"#2",
					"callback_data":"d2-"+proofdata["proof"]
				},
				{
					"text":"#3",
					"callback_data":"d3-"+proofdata["proof"]
				},
				{
					"text":"#4",
					"callback_data":"d4-"+proofdata["proof"]
				},
				{
					"text":"#5",
					"callback_data":"d5-"+proofdata["proof"]
				},
				{
					"text":"#6",
					"callback_data":"d6-"+proofdata["proof"]
				}
			],
			
			[
				{
					"text":"Roll the die!",
					"callback_data":"roll-"+proofdata["proof"]
				}
			]
		]}
		response_text = "Your wager of "+str(wager)+" LTC is ready. Choose some winning numbers below, and then press 'roll' to see if you won.\n\nYour proof hash is "+proofdata["proof"]+". The input that creates this proof will be revealed after you bet.\n\n<strong>What number(s) you like to bet on?</strong>"
		return response_text, keyboard_markup
	
	# Updates the state when a callback message is received from the button press
	def update_state(self, update):
		'''
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
		'''
		chunks = update.callback.split("-")
		game_choice = chunks[0]
		game_proof = chunks[1]
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		gamedata = cur.execute("select secret,salt,wager,state from gamedata where finished=0 and user_id=? and chat_id=? and proof=? limit 1",(update.from_id, update.data["message"]["chat"]["id"], game_proof)).fetchone()
		if gamedata == None:
			return False,0.0,None,None
		
		game_secret, game_salt, wager, game_state = gamedata
		game_state = json.loads(game_state)
		
		# process when users press the toggle buttons for choosing lucky numbers
		if game_choice.startswith("d"):
			keyboard_markup = {"inline_keyboard":[
				[
					{
						"text":"#1",
						"callback_data":"d1-"+game_proof
					},
					{
						"text":"#2",
						"callback_data":"d2-"+game_proof
					},
					{
						"text":"#3",
						"callback_data":"d3-"+game_proof
					},
					{
						"text":"#4",
						"callback_data":"d4-"+game_proof
					},
					{
						"text":"#5",
						"callback_data":"d5-"+game_proof
					},
					{
						"text":"#6",
						"callback_data":"d6-"+game_proof
					}
				],
				
				[
					{
						"text":"Roll the die!",
						"callback_data":"roll-"+game_proof
					}
				]
			]}
			
			if game_choice in game_state:
				removal_index = game_state.index(game_choice)
				del game_state[removal_index]
			else:
				game_state.append(game_choice)
			
			cur.execute("update gamedata set state=? where proof=? limit 1",(json.dumps(game_state),game_proof))
			db.commit()
			
			# if there are 0 chosen values after this update, use the default text and keyboard
			if len(game_state) == 0:
				response_text = "Your wager of "+str(wager)+" LTC is ready. Choose some winning numbers below, and then press 'roll' to see if you won.\n\nYour proof hash is "+game_proof+". The input that creates this proof will be revealed after you bet.\n\n<strong>What number(s) you like to bet on?</strong>"
				return True, 0.0, response_text, keyboard_markup
			# otherwise, calculate new payout value
			else:
				multiplier = (6./float(len(game_state)))*0.95
				payout = round(wager*multiplier,8)
				winning_choice_text = ", ".join(sorted(game_state))
				
				response_text = "Your wager of "+str(wager)+" LTC is ready. If you win, you will receive "+str(payout)+" LTC.\n\nYour chosen lucky numbers are: "+winning_choice_text+"\n\nYour proof hash is "+game_proof+". The input that creates this proof will be revealed after you bet.\n\n<strong>Wager: </strong>"+str(round(wager,8))+" LTC\n<strong>Multiplier: </strong>"+str(multiplier)+"x\n<strong>Potential payout: </strong>"+str(payout)+" LTC\n\n<strong>What number(s) you like to bet on?</strong>"
				return True, 0.0, response_text, keyboard_markup
		
		# process when users press the button to roll
		elif game_choice == "roll":
			if len(game_state) > 0:
				# win
				if game_secret in game_state:
					multiplier = (6./float(len(game_state)))*0.95
					payout = round(wager*multiplier,8)
					
					response_text = "The die landed on "+game_secret+" and you won!\n<strong>Wager: </strong>"+str(round(wager,8))+" LTC\n<strong>Multiplier: </strong>"+str(multiplier)+"x\n<strong>Payout: </strong>"+str(payout)+" LTC\n\n"
					#response_text = "The die landed on "+game_secret+" and you won "+str(payout)+" LTC from your wager of "+str(round(wager,8))+" LTC!\n\n"
				# lose
				else:
					multiplier = 0.0
					payout = 0.0
					
					response_text = "The die landed on "+game_secret+" and you lost! Better luck next time.\n\n"
				
				response_text += "Game proof: "+game_proof+"\n\nGame proof formula: sha256("+game_secret+":"+game_salt+")"
				
				cur.execute("update gamedata set finished=1 where proof=? limit 1",(game_proof,))
				db.commit()
			
				return True,payout,response_text,None
			
			else: # disallow rolling with no lucky numbers chosen
				return False,0.0,None,None

class CoinFlip(GameBase):
	def __init__(self):
		GameBase.__init__(self)
		self.default_game_state = json.dumps({"partner":None})
	
	def random_secret(self):
		is_heads = (float(ord(get_random_bytes(1)))/255.)>0.5
		if is_heads:
			return "heads"
		else:
			return "tails"
	
	# Creates a new game in the DB
	#   "update" is a Telegram.Update object, a text message sent to the bot
	# 
	# Returns a tuple
	# tuple[0] is the plaintext of the response (new message, not edit) and tuple[1] is the reply_markup
	def create_game(self, update, wager):
		# choose the winning value and create a proof hash
		winning_choice = self.random_secret()
		proofdata = self.create_proof(winning_choice)
		
		# calculate payout, since there is only one possible win value for this game
		payout = round(wager*1.95,8)
		
		# write this game's data into the database
		params = (
			proofdata["proof"],
			winning_choice,
			proofdata["salt"],
			
			update.from_id,
			update.data["chat"]["id"],
			update.data["message_id"],
			
			"coinflip",
			self.default_game_state,
			wager,
			0
		)
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		cur.execute("insert into gamedata values (?,?,?, ?,?,?, ?,?,?,?)", params)
		db.commit()
		
		# create response text and inline keyboard markup
		response_text = "Your wager of "+str(wager)+" LTC is ready. If you win this game, you will earn "+str(payout)+" LTC.\n\nYour proof hash is "+proofdata["proof"]+". The input that creates this proof will be revealed after you bet.\n\n<strong>What would you like to bet on?</strong>"
		keyboard_markup = {"inline_keyboard":[
			[
				{
					"text":"Heads",
					"callback_data":"heads-"+proofdata["proof"]
				},
				
				{
					"text":"Tails",
					"callback_data":"tails-"+proofdata["proof"]
				}
			]
			#[
			#	{
			#		"text":"(OTHER PLAYERS) Join game",
			#		"callback_data":"djoin-"+proofdata["proof"]
			#	}
			#]
		]}
		
		# send it out to the chat!
		return response_text, keyboard_markup
	
	# Updates the state (just finalizes the game, in this simple case) when a callback message is received from the button press
	def update_state(self, update):
		'''
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
		'''
		
		chunks = update.callback.split("-")
		game_choice = chunks[0]
		game_proof = chunks[1]
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		gamedata = cur.execute("select secret,salt,wager,user_id,chat_id,state from gamedata where finished=0 and proof=? limit 1",(game_proof,)).fetchone()
		if gamedata == None:
			return False,0.0,None,None
		
		game_secret, game_salt, wager, creator_uid, creator_chatid, game_state = gamedata
		
		# compatibility patch for older versions where state was an empty string by default
		if game_state=="":
			game_state = json.loads(self.default_game_state)
			partner = None
		else:
			game_state = json.loads(game_state)
			partner = game_state["partner"]
		
		# if this is the main user finalizing the game...
		if creator_uid == update.from_id and creator_chatid == update.data["message"]["chat"]["id"] and game_choice in ["heads","tails"]:
			if game_secret == game_choice:
				response_text = "You chose "+game_choice+" and won "+str(round(wager*1.95,8))+" LTC!\n\n"
				payout = round(wager*1.95,8)
			else:
				
				payout = 0.0
				
				if partner != None:
					response_text = "You chose "+game_choice+" and lost! Your partner won "+str(round(wager*1.95,8))+" LTC. Better luck next time.\n\n"
					self.credit(partner, round(wager*1.95,8), "won flip as partner")
				else:
					response_text = "You chose "+game_choice+" and lost! Better luck next time.\n\n"
			
			response_text += "Game proof: "+game_proof+"\n\nGame proof formula: sha256("+game_secret+":"+game_salt+")"
			
			cur.execute("update gamedata set finished=1 where proof=? limit 1",(game_proof,))
			db.commit()
			
			return True,payout,response_text,None
		
		# if this is from another user pressing the button to join
		if creator_uid != update.from_id and creator_chatid == update.data["message"]["chat"]["id"] and game_choice == "djoin" and partner == None:
			# check that the user has enough balance to join this game
			sender_balance = self.get_balance(update.from_id)
			print("sender_balance", sender_balance)
			
			if sender_balance >= wager:
				# deduct the wager from this player's balance
				self.debit(update.from_id, wager, "joined dice game")
				
				# update game state in the DB to show that a partner has joined
				new_state = json.dumps({"partner":update.from_id})
				cur.execute("update gamedata set state=? where proof=? limit 1",(new_state,game_proof))
				db.commit()
				
				# send an updated version of the game interface with the partner's name on it
				payout = round(wager*1.95,8)
				response_text = "Your wager of "+str(wager)+" LTC is ready. You are betting against @"+str(update.from_user.username)+"\nThe winner of this game will earn "+str(payout)+" LTC.\n\nYour proof hash is "+game_proof+". The input that creates this proof will be revealed after you bet.\n\n<strong>What would you like to bet on?</strong>"
				keyboard_markup = {"inline_keyboard":[
					[
						{
							"text":"Heads",
							"callback_data":"heads-"+game_proof
						},
						
						{
							"text":"Tails",
							"callback_data":"tails-"+game_proof
						}
					]
				]}
				
				return True,0.0,response_text,keyboard_markup
		
		return False,0.0,"",None
