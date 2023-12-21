signTXWithWIF = function (prefixPublic, prefixPrivate, prefixScriptHash, bech32HRP, hex, wif){
	coinjs.pub = parseInt("0x" + prefixPublic, 16);
	coinjs.priv = parseInt("0x" + prefixPrivate, 16);
	coinjs.multisig = parseInt("0x" + prefixScriptHash, 16);
	coinjs.bech32.hrp = bech32HRP;
	
	try {
		var tx = coinjs.transaction();
		var t = tx.deserialize(hex);

		var signed = t.sign(wif);
		return signed;
	} catch (e) {
		return 0;
	}
}
