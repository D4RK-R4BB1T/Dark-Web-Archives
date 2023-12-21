bech32EncodePublicKey = function (bech32HRP, publicKey){
	coinjs.bech32.hrp = bech32HRP;
	
	try {
		return coinjs.bech32Address(publicKey).address;
	} catch (e) {
		return false;
	}
}
