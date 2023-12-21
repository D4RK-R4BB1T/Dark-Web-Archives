createTXWithInputs = function (prefixPublic, prefixPrivate, prefixScriptHash, bech32HRP, inputs, outputs, redeemScript, nLockTime){
	coinjs.pub = parseInt("0x" + prefixPublic, 16);
	coinjs.priv = parseInt("0x" + prefixPrivate, 16);
	coinjs.multisig = parseInt("0x" + prefixScriptHash, 16);
	coinjs.bech32.hrp = bech32HRP;
	
	try {
		var inputs = JSON.parse(inputs);
		var outputs = JSON.parse(outputs);
	
		var tx = coinjs.transaction();
		tx.lock_time = nLockTime;

		for (var i = 0, len = inputs.length; i < len; i++){
			var o = inputs[i];
			var seq = null;
			var currentScript = redeemScript;
			
			if(currentScript.match(/^00/) && currentScript.length==44){
				s = coinjs.script();
				s.writeBytes(Crypto.util.hexToBytes(currentScript));
				s.writeOp(0);
				s.writeBytes(coinjs.numToBytes((o.value).toFixed(0), 8));
				currentScript = Crypto.util.bytesToHex(s.buffer);
			}
			
			tx.addinput(o.txid, o.vout, currentScript, seq);
		}
	
		for (var i = 0, len = outputs.length; i < len; i++){
			var o = outputs[i];
		
			var a = o.address;
			var ad = coinjs.addressDecode(a);
			if(((a!="") && (ad.version == coinjs.pub || ad.version == coinjs.multisig || ad.type=="bech32")) && o.value!=""){ // address
				tx.addoutput(a, o.value);
			} else {
				return false;
			}
		}

		return tx.serialize() + ',' + tx.size();
	} catch (e) {
		return false;
	}
}
