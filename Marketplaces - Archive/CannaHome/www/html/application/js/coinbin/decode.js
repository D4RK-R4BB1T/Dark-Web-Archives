decodeTX = function (prefixPublic, prefixPrivate, prefixScriptHash, bech32HRP, hex){
	coinjs.pub = parseInt("0x" + prefixPublic, 16);
	coinjs.priv = parseInt("0x" + prefixPrivate, 16);
	coinjs.multisig = parseInt("0x" + prefixScriptHash, 16);
	coinjs.bech32.hrp = bech32HRP;
	
	try {
		var tx = coinjs.transaction();
		var decode = tx.deserialize(hex);
	
		var outputs = [];
		for (var i = 0, len = decode.outs.length; i < len; i++){
			var output = decode.outs[i];
			var value = output.value;
			var address = '';
			
			if (output.script.chunks.length==5){
				address = coinjs.scripthash2address(Crypto.util.bytesToHex(output.script.chunks[2]));
			} else if((output.script.chunks.length==2) && output.script.chunks[0]==0){
				address = coinjs.bech32_encode(coinjs.bech32.hrp, [coinjs.bech32.version].concat(coinjs.bech32_convert(output.script.chunks[1], 8, 5, true)));
			} else {
				var pub = coinjs.pub;
				coinjs.pub = coinjs.multisig;
				address = coinjs.scripthash2address(Crypto.util.bytesToHex(output.script.chunks[1]));
				coinjs.pub = pub;
			}
		
			outputs.push([i, address, value]);
		}
	
		return JSON.stringify(outputs);
	} catch (e) {
		return 0;
	}
}
