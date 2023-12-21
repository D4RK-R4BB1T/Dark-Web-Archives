from bitcoinrpc.authproxy import AuthServiceProxy, JSONRPCException

class Litecoin:
	def __init__(self):
		self.cli = AuthServiceProxy("http://%s:%s@127.0.0.1:9332"%("USERNAME_REDACTED", "PASSWORD_REDACTED"))
	
	def address_received(self, address, conf=1):
		if not self.validate(address):
			return 0.0
		
		if type(conf) != int:
			return 0.0
		
		return self.cli.getreceivedbyaddress(address, conf)
	
	def balance(self, conf=0, account=None):
		assert type(conf)==int
		
		return self.cli.getbalance(account, conf)
	
	def new_address(self, account=None):
		if account == None:
			return self.cli.getnewaddress()
		else:
			return self.cli.getnewaddress(account)
	
	def validate(self, address):
		try:
			return self.cli.validateaddress(address)["isvalid"]
		except Exception as e:
			print("ERROR in Litecoin.validate: "+str(e))
			return False
	
	# returns a 2-value tuple
	# tuple[0] is a bool indicating success
	# tuple[1] is either a string indicating failure reason, or txid as a string
	def send(self, value, destination, comment=None):
		value = round(float(value), 8)
		if not self.validate(destination):
			return (False, "Invalid address")
		
		try:
			if comment == None:
				txid = self.cli.sendtoaddress(destination, value, comment, comment)
			else:
				txid = self.cli.sendtoaddress(destination, value)
			
			return (True, txid)
		except Exception as e:
			return (False, "Could not send ("+str(e)+")")
	
	# lists transactions involving a particular account
	def account_transactions(self, account):
		return self.cli.listtransactions(account)
	
	# returns a 2-value tuple
	# tuple[0] is a bool indicating success
	# tuple[1] is either a string indicating failure reason, or txid as a string
	def sendmany(self, destinations, comment=None):
		for addr,value in destinations.items():
			destinations[addr] = round(destinations[addr],8)
			if not self.validate(addr):
				return (False,"Invalid address")
		
		try:
			txid = self.cli.sendmany(None,destinations, 1, comment)
			return (True, txid)
		except Exception as e:
			return (False, "Could not send ("+str(e)+")")
