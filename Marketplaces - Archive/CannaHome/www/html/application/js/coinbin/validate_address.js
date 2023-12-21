validateAddress = function (prefixPublic, prefixScriptHash, bech32HRP, address){
	coinjs.pub = parseInt("0x" + prefixPublic, 16);
	coinjs.multisig = parseInt("0x" + prefixScriptHash, 16);
	coinjs.bech32.hrp = bech32HRP;
	
	try {
		var addressDecode = coinjs.addressDecode(address);
		return	addressDecode !== false &&
			addressDecode.type !== 'other'
				? 1
				: 0;
	} catch (e) {
		return 0;
	}
}
