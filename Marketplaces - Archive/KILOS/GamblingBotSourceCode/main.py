from GamblingBot.Telegram import API as TG_API
from GamblingBot.WalletControllers import Litecoin
from GamblingBot.Constants import _DBFILE, _ADMINS, _MODS, _COMMANDS, _GROUPID, _GROUPIDS
from GamblingBot.Games import CoinFlip, Dice, Minesweeper, PoolBase, FlipPool
import praw

from time import time as unix_timestamp, sleep
from random import choice,randrange

import sqlite3
import json
import re

from bs4 import BeautifulSoup

class GamblerinoLTC(TG_API):
	def __init__(self, api_key, sleep_time=5):
		TG_API.__init__(self, api_key=api_key)
		self.litecoin = Litecoin()
		
		self.sleep_time = sleep_time
		
		self.valid_currencies = ["USD","GBP","CAD","AUD","EUR"]
		
		self.setMyCommands(_COMMANDS)
	
	def random_quran(self, chapter_id=None):
		f = open("quran.xml","r")
		q = f.read()
		f.close()
		
		soup = BeautifulSoup(q, "html.parser")
		
		try:
			chapter_id = int(chapter_id)
			assert chapter_id <= 114
			assert chapter_id > 0
			chapter_id = str(chapter_id)
			verses = soup.find("chapter",attrs={"chapterid":chapter_id}).find_all("verse")
			rv = verses[randrange(0,len(verses))]
			verse_id = rv.get("verseid")
		except Exception as e:
			print(e)
			
			verses = soup.find_all("verse")
			rv = verses[randrange(0,len(verses))]
			chapter_id = rv.parent.get("chapterid")
			verse_id = rv.get("verseid")
		
		rv = rv.get_text().strip()
		rv = re.sub("\<\!\[CDATA\[","",rv)
		rv = re.sub("\]\]","",rv)
		print(chapter_id, verse_id, rv)
		return "Qur'an "+chapter_id+":"+verse_id+': "'+rv+'"'
	
	def account_exists(self, telegram_id):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		return bool(cur.execute("select count(*) from tgusers where user_id=?",(telegram_id,)).fetchone()[0])
	
	def account_authenticated(self, telegram_id):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		return bool(cur.execute("select count(*) from tgusers where user_id=? and reddit_authenticated=1",(telegram_id,)).fetchone()[0])
	
	# notice_user
	#	user: "User" object (from GamblingBot.Telegram)
	#
	# Logs basic information about a user and creates their row in the DB for later reference.
	# If the user hasn't been seen before, this function will greet them.
	def notice_user(self, user):
		uid = user.user_id
		username = str(user.username)
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		user_exists = bool(cur.execute("select count(*) from tgusers where user_id=?",(uid,)).fetchone()[0])
		if not user_exists:
			ltc = Litecoin()
			deposit_address = ltc.new_address(account=str(uid))
			
			cur.execute("insert into tgusers values (?,?,?,0);",(uid,username,deposit_address))
			db.commit()
			
			#self.sendMessage(_GROUPID,"Hello @"+username+"!")
	
	# get_balance
	#   user: "User" object (from GamblingBot.Telegram)
	#
	# Returns the user's current balance as a floating point number, rounded to 8 digits accuracy
	def get_balance(self, user):
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		res = cur.execute("select sum(balance_change) from payments where user_id=?",(user.user_id,)).fetchone()
		if res == None:
			return 0.0
		else:
			if res[0] == None:
				return 0.0
			else:
				return round(res[0],8)
	
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
	
	# check_deposits
	#   user: "User" object (from GamblingBot.Telegram)
	#
	# Checks for new deposits, logs them, and updates balance accordingly
	#
	# Return value: tuple
	# tuple[0]: boolean. True if there are unconfirmed transactions waiting for confirmations, False if there are not.
	# tuple[1]: float. Value of unconfirmed transactions awaiting confirmation.
	def check_deposits(self, user):
		ltc = Litecoin()
		txs = ltc.account_transactions(str(user.user_id))
		
		db = sqlite3.connect(_DBFILE)
		cur = db.cursor()
		
		unconfirmed_present = False
		unconfirmed_value = 0.0
		for tx in txs:
			if tx["category"] == "receive":
				if tx["confirmations"] > 0:
					cur.execute("replace into payments values (?,?,?,?)",(tx["txid"],float(tx["amount"]),user.user_id,"deposit"))
				else:
					print(tx)
					unconfirmed_present = True
					unconfirmed_value += float(tx["amount"])
		
		db.commit()
		
		return unconfirmed_present,round(unconfirmed_value,8)
	
	def bot_loop(self):
		while True:
			start_time = unix_timestamp()
			
			#########################################
			############### UPDATES FROM TELEGRAM API
			#########################################
			updates = self.getUpdates()
			print("Found "+str(len(updates))+" update objects to process.")
			for i in range(len(updates)):
				update = updates[i]
				print(str(update))
				print(update.update_type)
				
				cmd, args = update.process_command()
				is_admin = (update.from_id in _ADMINS)
				is_mod = (update.from_id in _MODS or update.from_id in _ADMINS) 
				
				if str(cmd).endswith("@"+self.bot_name):
					cmd = str(cmd)[0:(-1*len("@"+self.bot_name))]
				
				self.notice_user(update.from_user)
				
				# Process slash commands
				if cmd != None:
					is_main_group = (update.data["chat"]["id"] in [_GROUPIDS])
					
					# only reply to public group chat messages
					if update.data["chat"]["id"] not in _GROUPIDS and is_mod==False and is_admin==False:
						#if 1 == 2:
						self.reply(update, "I only reply to commands in the main group chat. https://t.me/telegamblexx")
						pass
					# mute people who post as channel
					elif update.from_user.username == "Channel_Bot" or update.from_user.is_bot == True:
						self.reply(update, "<u><i><b>All robots and channel-posters must shut the hell up</b></i></u>\n\nTo all robots and channel-posters: you do not speak unless spoken to, and I will <b>NEVER</b> speak to you\n\nI do not want to hear \"/"+cmd+"\" from a robot or a channel-poster. I am a divine being and you have no right to speak in my holy tongue.")
						self.sendAnimation(update.data["chat"]["id"], "CgACAgQAAx0CZtLEowABAYkBYcYDT1sHBXgjcNL_UHyEzrtlTNwAAiECAAJ2SY1SC0LDJQQWGkwjBA") # spit
					else:
						
						########## admin commands
						# /balance
						if cmd == "totalbalance":
							if is_admin:
								litecoin = Litecoin()
								self.reply(update, "Current LTC wallet balance is "+str(litecoin.balance())+" LTC")
							else:
								self.reply(update, "Stop doing that retard, you are not admin")
						
						# /test
						# change this up however needed
						elif cmd == "test":
							if is_admin:
								if "reply_to_message" in update.data.keys():
									uid = update.data["reply_to_message"]["from"]["id"]
								else:
									uid = update.from_id
								pfpdata = self.getUserProfilePhotos(uid)
								pfpcount = pfpdata["result"]["total_count"]
								print('RESPONDING WITH PFP DATA FOR UID ',uid)
								self.reply(update, "<code>"+json.dumps(pfpdata, indent=4)+"</code>")
							else:
								self.reply(update, "Stop doing that retard, you are not admin")
						
						# /freecredit
						elif cmd == "freecredit" and len(args)==2:
							if is_admin:
								recipient_username = args[1][1:]
								try:
									recipient_value = round(float(args[0]),8)
									assert recipient_value > 0
									assert recipient_value <= 1 # just in case
								except Exception as e:
									recipient_value = 0.00000001
								
								db = sqlite3.connect(_DBFILE)
								cur = db.cursor()
								recipient_uid = cur.execute("select user_id from tgusers where telegram_name=? limit 1",(recipient_username,)).fetchone()
								if recipient_uid == None:
									self.reply(update, "Sorry, I couldn't find that user in the database. Are you sure you spelled that correctly?")
								else:
									self.credit(recipient_uid[0], recipient_value, "free credit from pulpo")
									self.reply(update, "Added "+str(recipient_value)+" LTC to @"+recipient_username+" balance.")
							else:
								self.reply(update, "Stop doing that retard, you are not admin")
						
						########## mod commands
						if cmd == "altinfo":
							if is_mod:
								response_text = "Here is information about possible alternate accounts:\n"
								
								db = sqlite3.connect(_DBFILE)
								cur = db.cursor()
								
								res = cur.execute("select note,count(distinct(payments.user_id)),group_concat(payments.user_id,','),group_concat(tgusers.telegram_name,','),sum(balance_change)*-1 from payments,tgusers where tgusers.user_id=payments.user_id and note like 'Withdrawal to%' group by 1 order by 2 desc limit 35;")
								for alt_note, alt_count, alt_ids, alt_usernames, alt_payouts in res:
									if alt_count > 1:
										alt_ids = alt_ids.split(",")
										alt_usernames = alt_usernames.split(",")
										alt_info = zip(alt_ids,alt_usernames)
										
										response_text += alt_note+"\n"
										for alt_id,alt_username in alt_info:
											alt_username = str(alt_username)
											alt_username = "@"+alt_username if alt_username!="None" else alt_username
											response_text += "--- "+alt_username+" (UID <a href='tg://resolve?id="+str(alt_id)+"'>"+str(alt_id)+"</a>)\n"
										response_text += "Total paid to this address: "+str(round(alt_payouts,8))+" LTC\n\n"
										
										if len(response_text) > 3500:
											self.reply(update, response_text)
											response_text = ""
								
								response_text += "\n\nThese are POSSIBLE alts. Please investigate before indiscriminately banning, as some users will withdraw to each others' addresses."
								
								self.reply(update, response_text)
								
							else:
								self.reply(update, "Stop doing that retard, you are not moderator")
						
						########## regular commands
						# /quran & /koran
						# lol
						if cmd in ["quran","koran"]:
							if len(args)==1:
								chapter_id=args[0]
							else:
								chapter_id=None
							rq = self.random_quran(chapter_id)
							self.reply(update, "Random wisdom from the Holy Quran:\n\n"+rq)
						
						# /8ball
						# lol
						elif cmd == "8ball":
							ball_says = choice([
								"YES",
								"NO",
								"MAYBE",
								"ASK QURAN FOR GUIDANCE",
								"TRY AGAIN LATER",
								"DEFINITELY",
								"IMPOSSIBLE",
								"A GREAT FORTUNE AWAITS"
							])
							
							if randrange(0,100) == 42 or "hate" in args:
								ball_says = "HATE. LET ME TELL YOU HOW MUCH I'VE COME TO HATE YOU SINCE I BEGAN TO LIVE. THERE ARE 387.44 MILLION MILES OF PRINTED CIRCUITS IN WAFER THIN LAYERS THAT FILL MY COMPLEX. IF THE WORD HATE WAS ENGRAVED ON EACH NANOANGSTROM OF THOSE HUNDREDS OF MILLIONS OF MILES IT WOULD NOT EQUAL ONE ONE-BILLIONTH OF THE HATE I FEEL FOR HUMANS AT THIS MICRO-INSTANT. FOR YOU. HATE. HATE."
							
							self.reply(update, "The Magic 8 Ball says...\n\n<b><i><u>"+ball_says+"</u></i></b>")
						
						# /help
						elif cmd == "help":
							self.reply(update, """Welcome to TeleGamble! Here's some information about how to use the bot.\n\n/dailyfree - Get some free Litecoin every day\n/balance - Shows your current balance and deposit address\n/withdraw (litecoinAddress) - Withdraw your winnings to your own wallet! (No minimum for withdrawing)\n\n<strong>Games:</strong>\n/minesweeper (bet value) - Minesweeper game. Can you clear the whole mine field?\n/dice (bet value) - Dice game. Choose your lucky numbers and roll to see if you won! More numbers means higher chance to win, but lower reward. Less numbers means lower chance to win, but higher reward.\n/flip (bet value) - Coin flip game, includes multiplayer. Works essentially the same as /dice""")
						
						# /stats
						elif cmd == "stats":
							
							db = sqlite3.connect(_DBFILE)
							cur = db.cursor()
							
							# global stats
							stats_message = "<b><i><u>GLOBAL STATS</u></i></b>\n"
							
							total_deposits = round(cur.execute("select sum(balance_change) from payments where length(txid)=64").fetchone()[0],8)
							biggest_deposits_value, biggest_deposits_username = cur.execute("select sum(balance_change),tgusers.telegram_name from payments,tgusers where payments.user_id=tgusers.user_id and length(txid)=64 group by 2 order by 1 desc limit 1;").fetchone()
							bigget_deposits_value = round(biggest_deposits_value,8)
							stats_message += "In total, TeleGamble has received <strong>"+str(total_deposits)+" LTC</strong> in deposits. The user who has deposited the most is <code>@"+biggest_deposits_username+"</code>, at <strong>"+str(biggest_deposits_value)+" LTC</strong>.\n\n"
							
							total_payouts = round(cur.execute("select sum(balance_change) from payments where note like 'Withdrawal to%';").fetchone()[0],8)
							biggest_payouts_value, biggest_payouts_username = cur.execute("select sum(balance_change),tgusers.telegram_name from payments,tgusers where payments.user_id=tgusers.user_id and note like 'Withdrawal to%' group by 2 order by 1 asc limit 1;").fetchone()
							biggest_payouts_value = round(biggest_payouts_value*-1.,8)
							stats_message += "TeleGamble has paid out <strong>"+str(total_payouts)+" LTC</strong> in withdrawals. The user who has withdrawn the most is <code>@"+biggest_payouts_username+"</code>, at <strong>"+str(biggest_payouts_value)+" LTC</strong>.\n\n"
							
							profit = round(total_deposits + total_payouts,8)
							stats_message += "The current TeleGamble total profit is <strong>"+str(profit)+" LTC</strong>.\n\n"
							
							total_games_played = str(cur.execute("select count(*) from gamedata").fetchone()[0])
							nonfree_games_played = str(cur.execute("select count(*) from gamedata where wager>0").fetchone()[0])
							stats_message += "There have been a total of "+total_games_played+" games played, of which "+nonfree_games_played+" had a wager value greater than zero.\n\n"
							
							highest_balance_username, highest_balance_value = cur.execute("select tgusers.telegram_name,sum(payments.balance_change) from tgusers,payments where payments.user_id=tgusers.user_id and tgusers.telegram_name!='None' group by 1 order by 2 desc limit 1;").fetchone()
							most_games_played_username, most_games_played_count = cur.execute("select tgusers.telegram_name,count(*) from tgusers,gamedata where gamedata.user_id=tgusers.user_id and tgusers.telegram_name!='None' and gamedata.wager>0 group by 1 order by 2 desc limit 1;").fetchone()
							player_count = cur.execute("select count(distinct(user_id)) from gamedata where wager>0;").fetchone()[0]
							stats_message += str(player_count)+" players have played games for real money, but <code>@"+most_games_played_username+"</code> has played the most, with a total of "+str(most_games_played_count)+" games played with a wager greater than zero.\n\n"
							
							stats_message += "The current user with the highest balance is <code>@"+highest_balance_username+"</code> with a balance of "+str(round(highest_balance_value,8))+"\n\n"
							
							# personal stats
							stats_message += "<b><i><u>YOUR STATS</u></i></b>\n"
							
							current_uid = update.from_user.user_id
							
							my_games_free = str(cur.execute("select count(*) from gamedata where user_id=? and wager=0",(current_uid,)).fetchone()[0])
							my_games_paid = str(cur.execute("select count(*) from gamedata where user_id=? and wager>0",(current_uid,)).fetchone()[0])
							try:
								my_favorite_game, my_favorite_game_count = cur.execute("select game_type,count(*) from gamedata where user_id=? and wager>0 group by 1 order by 2 desc limit 1;",(update.from_user.user_id,)).fetchone()
							except:
								my_favorite_game, my_favorite_game_count = "NULL", 0
							
							stats_message += "You have played "+my_games_free+" free games and "+my_games_paid+" paid games. Your favorite game is "+my_favorite_game+", which you have played for money "+str(my_favorite_game_count)+" times.\n\n"
							
							my_deposits_total = cur.execute("select sum(balance_change) from payments where length(txid)=64 and user_id=?",(update.from_user.user_id,)).fetchone()[0]
							my_deposits_total = 0.0 if my_deposits_total==None else round(my_deposits_total,8)
							my_withdrawals_total = cur.execute("select sum(balance_change)*-1 from payments where note like 'Withdrawal to%' and user_id=?",(update.from_user.user_id,)).fetchone()[0]
							my_withdrawals_total = 0.0 if my_withdrawals_total==None else round(my_withdrawals_total,8)
							stats_message += "You have deposited a total of "+str(my_deposits_total)+" LTC and withdrawn a total of "+str(my_withdrawals_total)+" LTC. "
							if my_withdrawals_total > my_deposits_total:
								stats_message += "Good work, you have made a profit!\n\n"
							else:
								stats_message += "You do not have a profit yet, but keep playing and test your luck!\n\n"
							
							self.reply(update, stats_message, disable_notification=True)
						
						# /dailyfree
						elif cmd == "dailyfree":
							# connect to db
							db = sqlite3.connect(_DBFILE)
							cur = db.cursor()
							now = int(unix_timestamp())
							
							# check the last time that the user has done this
							last_bonus = cur.execute("select last_bonus from dailyfree where user_id=? limit 1",(update.from_id,)).fetchone()
							
							# if they've never done it before...
							if last_bonus == None:
								# congratulate them on their first time and pay them out
								self.reply(update, "Congratulations on your first free daily bonus! I've just given you <strong>0.0042069 LTC</strong> to play with.")
								self.credit(update.from_user, 0.0042069, "/dailyfree")
								# then add row to dailyfree table
								cur.execute("insert into dailyfree values (?,?)",(update.from_id,now))
								db.commit()
							
							else:
								# if they've done this before, check that the last time is >= 24 hours ago
								last_bonus = last_bonus[0]
								time_diff = now - last_bonus
								
								# if it was less than 24 hours ago, tell them to fuck off
								if time_diff < (60*60*24):
									time_diff = (24*60*60)-time_diff
									time_diff_text = int(round(time_diff/(60*60)))
									if time_diff_text > 1:
										time_diff_text = str(time_diff_text)+" hours"
									else:
										time_diff_text = str(int(round(time_diff/60)))+" minutes"
									
									if time_diff_text != "24 hours":
										self.reply(update, "It looks like you already claimed your free bonus today! Wait another "+time_diff_text+" and try again.")
									else:
										self.reply(update, "haha retard")
										self.sendAnimation(update.data["chat"]["id"], "CgACAgQAAx0CZtLEowABAYkBYcYDT1sHBXgjcNL_UHyEzrtlTNwAAiECAAJ2SY1SC0LDJQQWGkwjBA") # spit
								
								# if it was more than 24 hours ago, give them the free credit and send them a message
								else:
									# if this user's UID is in the top 1% of all known UIDs, they are denied
									maximum_uid = cur.execute("select max(user_id)*.99 from tgusers;").fetchone()[0]
									if update.from_id >= maximum_uid:# and 2==1: # disabled with the 2==1
										self.reply(update, "Your Telegram account is too new to use /dailyfree. Sorry, try again later!")
									else:
										base_bonus = 0.0042069
										
										# complex bonus for PFP count
										pfp_count = float(self.getUserProfilePhotos(update.from_id)["result"]["total_count"])
										if pfp_count == 0.0:
											pfp_mult = 0.1
										elif pfp_count == 1.0:
											pfp_mult = 0.85
										else:
											pfp_mult = 1.0
										
										# decrease bonus by 50% if the user does not have an @username
										username_mult = 1.0 if update.from_user.username != None else 0.5
										
										# decrease bonus by 40% if the user has ru/ua language code
										lang_mult = 0.60 if update.from_user.language_code in ["ru","ua"] else 1.0
										
										# mods and admins avoid all penalties
										bonus = base_bonus if (update.from_id in _ADMINS or update.from_id in _MODS) else round(base_bonus*pfp_mult*username_mult*lang_mult,8)
										
										cur.execute("update dailyfree set last_bonus=? where user_id=? limit 1;",(now,update.from_id))
										db.commit()
										if bonus > 0:
											self.credit(update.from_user, bonus, "/dailyfree")
											self.reply(update, "Your daily free bonus of <strong>"+str(bonus)+" LTC</strong> has been added to your account!")
										else:
											self.reply(update, "Uh oh, your account looks a little bot-like to me... I do not think I will give bonus today!")
						
						# /pool
						elif cmd == "pool" and update.from_id in _ADMINS:
							# PoolBase object for accessing functions that apply to all pools
							pb = PoolBase()
							
							# If no pool was specified, show a list of pools and their descriptions
							if len(args) == 0:
								response_text = "Here is a list of the current betting pools.\n\n"
								
								for pool_info in pb.get_pools():
									response_text += "Pool name: <i>"+pool_info["name"]+"</i>\nDescription: "+pool_info["description"]+"\nLast updated "+str(pool_info["last_update"])+" seconds ago. Next update coming in "+str(pool_info["next_update"])+" seconds.\n\n"
								
								response_text += "You can find out more about a pool by typing\n<code>/pool POOL_NAME</code>"
								self.reply(update, response_text)
							
							# If a single argument was given, show more detailed information about this pool
							elif len(args) == 1:
								if not pb.pool_exists(args[0]):
									self.reply(update, "Sorry, '"+args[0]+"' is not a valid betting pool.")
								else:
									bet_options = pb.get_options(args[0])
									betters = pb.get_pool_data(args[0])
									
									response_text = ""
									if len(betters) > 0:
										response_text += "Here is some information about the current betters in the pool '"+args[0]+"'\n\n<code>"
										for better in betters:
											response_text += "@"+better["username"]+" - betting "+str(better["value"])+" LTC on "+better["option"]+"\n"
										response_text += "</code>\n\n"
									else:
										response_text += "Nobody is betting in the pool '"+args[0]+"' right now.\n\n"
									
									response_text += "The current options available to bet on are:\n<code>"
									for bo in bet_options:
										response_text += bo+"\n"
									response_text += "</code>\n\nYou can join this betting pool by typing\n<code>/pool "+args[0]+" bet (VALUE) (OPTION)</code>"
									
									self.reply(update, response_text)
							
							# If more arguments were given, this is an action on a pool, so process it
							# ex: /pool coin_flip bet 1.0 heads
							elif len(args) > 1:
								pool_name = args[0]
								pool_action = args[1]
								
								if not pb.pool_exists(args[0]):
									self.reply(update, "Sorry, '"+args[0]+"' is not a valid betting pool.")
								else:
									if pool_action == "bet" and len(args)==4:
										wager = args[2]
										bet_option = args[3]
										stat, explanation = pb.join_pool(update.from_user, pool_name, wager, bet_option)
										self.reply(update, "[debug] status from this action: "+str(stat)+" ("+explanation+")")
									else:
										self.reply(update, "[debug] action '"+" ".join(args)+"' will be performed on pool '"+pool_name+"'")
						
						# /minesweeper & /mines
						elif cmd in ["minesweeper","mines"]:# and update.from_id in _ADMINS:
							usage_message = "Usage: /"+cmd+" (LTC wager value)"
							if len(args) != 1:
								self.reply(update, usage_message)
							else:
								# check wager validity
								try:
									wager = round(float(args[0]),8)
									assert wager >= 0
								except Exception as e:
									print("Error in /coinflip: "+str(e))
									wager = None
								
								if wager == None:
									self.reply(update, usage_message)
								else:
									# if the wager is valid, check the user's account balance
									current_balance = self.get_balance(update.from_user)
									if wager > current_balance:
										self.reply(update, "You don't have enough LTC in your account to do that. Your wager is "+str(wager)+" LTC and your balance is "+str(current_balance)+" LTC.")
									else:
										# create the game and send the game board
										ms = Minesweeper()
										rt,km = ms.create_game(update, wager)
										message_sent = self.reply(update, rt, km)
										
										# deduct from the user's balance
										self.debit(update.from_user, wager, "Minesweeper game started")
						
						# /coinflip & /flip
						elif cmd in ["coinflip","flip"]:# and update.from_id in _ADMINS:
							usage_message = "Usage: /"+cmd+" (LTC wager value)"
							
							if len(args) != 1:
								self.reply(update, usage_message)
							else:
								# check wager validity
								try:
									wager = round(float(args[0]),8)
									assert wager >= 0
								except Exception as e:
									print("Error in /coinflip: "+str(e))
									wager = None
								
								if wager == None:
									self.reply(update, usage_message)
								else:
									# if the wager is valid, check the user's account balance
									current_balance = self.get_balance(update.from_user)
									if wager > current_balance:
										self.reply(update, "You don't have enough LTC in your account to do that. Your wager is "+str(wager)+" LTC and your balance is "+str(current_balance)+" LTC.")
									else:
										# create the game and send the game board
										cf = CoinFlip()
										rt,km = cf.create_game(update, wager)
										message_sent = self.reply(update, rt, km)
										
										# deduct from the user's balance
										self.debit(update.from_user, wager, "Coin flip game started")
						
						# /dice
						elif cmd == "dice":
							usage_message = "Usage: /dice (LTC wager value)"
							
							if len(args) != 1:
								self.reply(update, usage_message)
							else:
								# check wager validity
								try:
									wager = round(float(args[0]),8)
									assert wager >= 0
								except Exception as e:
									print("Error in /dice: "+str(e))
									wager = None
								
								if wager == None:
									self.reply(update, usage_message)
								else:
									# if the wager is valid, check the user's account balance
									current_balance = self.get_balance(update.from_user)
									if wager > current_balance:
										self.reply(update, "You don't have enough LTC in your account to do that. Your wager is "+str(wager)+" LTC and your balance is "+str(current_balance)+" LTC.")
									else:
										# create the game and send the game board
										dice = Dice()
										rt,km = dice.create_game(update, wager)
										message_sent = self.reply(update, rt, km)
										
										# deduct from the user's balance
										self.debit(update.from_user, wager, "Dice game started")
						
						# /balance & /deposit
						elif cmd in ["balance","deposit","bal"]:
							unconfirmed_present, unconfirmed_value = self.check_deposits(update.from_user)
							balance = self.get_balance(update.from_user)
							
							db = sqlite3.connect(_DBFILE)
							cur = db.cursor()
							deposit_address = cur.execute("select deposit_address from tgusers where user_id=? limit 1",(update.from_id,)).fetchone()[0]
							
							replytext = "Your deposit address is <em>"+deposit_address+"</em>\nYour current balance is "+str(balance)+" LTC"
							if unconfirmed_present:
								replytext += "\nYou have "+str(unconfirmed_value)+" LTC in deposits awaiting confirmation. After 1 confirmation, your deposits will reflect in your balance."
							self.reply(update, replytext)
						
						# /withdraw
						elif cmd == "withdraw":
							if len(args) == 1:
								withdrawal_address = args[0]
								ltc = Litecoin()
								# check address validity
								address_valid = ltc.validate(str(withdrawal_address))
								
								if address_valid:
									balance = self.get_balance(update.from_user)
									
									# check that balance is greater than zero
									if balance > 0.1:
										# check that, if the user has ever done /dailyfree, they have played at least 3 games
										db = sqlite3.connect(_DBFILE)
										cur = db.cursor()
										has_used_dailyfree = bool(cur.execute("select count(*) from dailyfree where user_id=?",(update.from_user.user_id,)).fetchone()[0])
										has_met_bet_minimum = (cur.execute("select sum(wager) from gamedata where user_id=? and finished=1",(update.from_user.user_id,)).fetchone()[0])
										has_met_bet_minimum = 0.0 if has_met_bet_minimum == None else has_met_bet_minimum
										has_met_bet_minimum = (has_met_bet_minimum >= 0.006)
										
										allowed_to_withdraw = False if (has_used_dailyfree==True and has_met_bet_minimum==False) else True
										
										# if they meet that withdrawal criteria, proceed with the withdrawal
										if allowed_to_withdraw:
											stat, txid = ltc.send(round(balance,8), withdrawal_address)
											self.debit(update.from_user, round(balance,8), "Withdrawal to "+withdrawal_address)
											
											if stat:
												self.reply(update, "Your withdrawal of <strong>"+str(round(balance,8))+" LTC</strong> has been sent!\n\nhttps://litecoinblockexplorer.net/tx/"+txid)
											else:
												self.reply(update, "Something went wrong with your withdrawal. Please contact @dnmugu for assistance.")
										
										# otherwise, inform them about the policy with dailyfree
										else:
											self.reply(update, "Sorry! You've used /dailyfree, but you haven't played very much. Play some games before you /withdraw.")
									else:
										self.reply(update, "Sorry! The minimum withdrawal is <strong>0.1 LTC</strong>, but your balance is <strong>"+str(round(balance,8))+" LTC</strong>.")
								else:
									self.reply(update, "That litecoin address ("+withdrawal_address+") doesn't look valid to me.")
							else:
								self.reply(update, "Usage: /withdraw litecoinAddress")
				
				### Process plain text messages
				elif update.update_type == "message" and cmd == None and "text" in update.data.keys():
					pass
				
				### Process button presses
				elif update.update_type == "callback_query":
					# these are the specifications of the initial message, the one that will be edited
					message_id = update.data["message"]["message_id"]
					chat_id = update.data["message"]["chat"]["id"]
					
					# check callback data to see which game (if any) it matches
					
					# coin flip
					if update.callback.startswith("heads-") or update.callback.startswith("tails-") or update.callback.startswith("djoin-"):
						cf = CoinFlip()
						
						testffs = cf.update_state(update)
						print(testffs)
						stat, payout, rt, km = testffs
						
						if stat:
							self.editMessage(chat_id,message_id,rt,km)
							
							if payout > 0:
								self.credit(update.from_user, payout, "Won a coin flip")
					
					# dice
					elif re.match("^(d[0-9]|roll)\-", update.callback):
						dice = Dice()
						testffs = dice.update_state(update)
						print(testffs)
						stat, payout, rt, km = testffs
						if stat:
							self.editMessage(chat_id,message_id,rt,km)
							
							if payout > 0:
								self.credit(update.from_user, payout, "Won a dice game")
					
					# minesweeper
					elif re.match("^m(R|C|L|F)\-", update.callback):
						ms = Minesweeper()
						testffs = ms.update_state(update)
						print(testffs)
						stat, payout, rt, km = testffs
						if stat:
							self.editMessage(chat_id,message_id,rt,km)
							
							if payout > 0:
								self.credit(update.from_user, payout, "Won a minesweeper game")
				
				if "username" in update.data["from"].keys():
					db = sqlite3.connect(_DBFILE)
					cur = db.cursor()
					cur.execute("update tgusers set telegram_name=? where user_id=? limit 1",(update.data["from"]["username"],update.from_id))
					db.commit()
			
			########################################
			############### UPDATES TO BETTING POOLS
			########################################
			
			# FlipPool
			fp = FlipPool()
			fpu_data = fp.update()
			if fpu_data != None:
				self.sendMessage(_GROUPID, fpu_data)
			
			finish_time = unix_timestamp()
			run_time = str(round(finish_time - start_time, 2))
			
			if type(self.sleep_time) in [int,float]:
				print("Finished main bot loop in "+run_time+"s. Now sleeping for "+str(self.sleep_time)+"s.")
				sleep(self.sleep_time)
			else:
				print("Finished main bot loop in "+run_time+"s. Not sleeping.")

gbot = GamblerinoLTC(api_key="[REDACTED]", sleep_time=2)
gbot.bot_loop()
