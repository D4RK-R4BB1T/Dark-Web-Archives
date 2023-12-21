Download Bitcoin core from official https://bitcoin.org/en/bitcoin-core/

after instlation run your bitcoind 

bitcoind & 

it will create new directiry in root .bitcoind 

cd ~/.bitcoind/
 then create bitcoin.conf

nano bitcoin.conf


** Add config to bitcoin.conf file ** 

rpcuser=nodeuser
rpcpassword=nodepassword
testnet=1
rpcport=8332
rpcallowip=127.0.0.1
server=1


and restart bitcoind 


test with curl 



curl --user nodeuser:nodepassword --data-binary '{"jsonrpc": "1.0", "id":"curltest", "method": "getnewaddress", "params": [] }' -H 'content-type: text/plain;' http://127.0.0.1:8332/ -w '%{http_code}\n'
if it works changes rpcuser and rpcpassword in .env file for bitcoin 




