<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;
use BitWasp\BitcoinLib\Electrum;

require_once(__DIR__. '/../vendor/autoload.php');

// Prompt for the redeem script which created the address.
// Sets $decode_redeem_script, $redeem_script, $address, and $script_hash

//$redeem_script = '522103793882c7025f32d2bbdd07f145fbf16fea83df6352ef42e38d4137f5a24975cc2102e67b93adc52dfdfa181311610244d98811ecd0a26be65a10b720f417cb8997904104492368cd25892f3a6a618ce750e28f4f1c3c9fec4abd67287cb6450356abc0cfef3358cd39b5390c7ea7e053358c97463741d22d1e9cd2c2d3a528d137fb6bf553ae';
//$decode_redeem_script = RawTransaction::decode_redeem_script($redeem_script);
//$script_hash = BitcoinLib::hash160($redeem_script);
//$address = BitcoinLib::hash160_to_address($script_hash, '05');

//$raw_transaction = '0100000001c723ef78f22d8563b2d1d61aee1904ef12e28524b87122db87c00d6ebc057c9600000000d40047304402203432bff2b897fe10291ec8a66449cd09d850729434d75207de6b4be843a6596b02201aeeb571d03165974215e50f8fca0dc05d35febbbc95d312cab3cf5ebc337085014c8952210217e9f0793d77e4af65e7b3c158459d51edc54011d24b3d57b9945ac4a3d377852103f868e850aed9be2513b7194cf14421ad8b6ec98f65dbdb8264d8be63d8f6ff17410400a0702140404c2e90b3a50bdcb47efb80afa8f4f3a5ae3226bf9027c3c644ce839aaad770ca287615d123e962afd1a7266974cb8da5c5468ffe3aa41c41f03453aeffffffff02d8270000000000001976a914b24490dbbd5e0f02e7891786991aadd869b5e75b88ac584d0000000000001976a914163f9c401b613a2bc7d1e331972788da0d66f6cc88ac00000000';
//$decoded_transaction = RawTransaction::decode($raw_transaction);
//$json_str = '';
//$seed = '6f45ad7f901d7421a6d20d9e797bb73b';

while ( ! isset($redeem_script))
{
	echo "Enter redeem script: ";
	
	$line = trim(fgets(STDIN));
	$decode_redeem_script = RawTransaction::decode_redeem_script($line);
	
	if ($decode_redeem_script == FALSE)
	{
		echo "Not a valid script!\n\n";
		unset($decode_redeem_script);
	}
	else
	{
		$redeem_script = $line;
		$script_hash = BitcoinLib::hash160($redeem_script);
		$address = BitcoinLib::hash160_to_address($script_hash,'05');
		echo "Learned about {$decode_redeem_script['m']} of {$decode_redeem_script['n']} address: ".$address."\n\n";
	}
}

// Prompt for raw transaction.
// Sets $raw_transaction, and $decoded_transaction
while ( ! isset($raw_transaction) )
{
	echo "Enter a raw transaction to sign: ";
	
	$line = trim(fgets(STDIN));
	$multi = explode(" ", $line);
	$decoded_transaction = RawTransaction::decode($multi[0]);
	if($decoded_transaction !== FALSE)
	{
		$raw_transaction = $multi[0];
	}
	else
	{
		echo "Not a valid raw transaction, or unable to decode.\n\n";
		unset($decoded_transaction);
	}
	
	// Try to set the JSON inputs from the given transaction.
	if (isset($multi[1]))
	{
		$dec = json_decode(str_replace("'", "", $multi[1]));
		$test = TRUE;
		array_walk($dec, function($e) use (&$test) {
			if(!is_object($e))
				$test = FALSE;
		});
		
		if($test == TRUE)
			$json_maybe = str_replace("'", "", $multi[1]);
	}
	
}

// Prompt for JSON inputs
// Sets $json_inputs.
while ( ! isset($json_str) )
{
	if(isset($json_maybe)) {
		$try = json_decode($json_maybe);
		if ( is_array($try)  )
		{
			$to_check	= count($try);
			$i			= 0; 
			foreach ($try as &$input)
			{
				if (isset($input->txid) AND $decoded_transaction['vin'][$i]['txid'] == $input->txid
				AND	isset($input->vout) AND $decoded_transaction['vin'][$i]['vout'] == $input->vout
				AND	isset($input->scriptPubKey))
				{
					$tx_info = RawTransaction::_get_transaction_type(RawTransaction::_decode_scriptPubKey($input->scriptPubKey));
					if ($tx_info['hash160'] == $script_hash)
						$input->redeemScript = $redeem_script;

					$to_check--; 
				}
				$i++;
			}
			if ($to_check == 0)
			{
				$json_str = json_encode($try);
				break;
			}
		}
	}
	
	
	echo "\nEnter input data as JSON string (or 'x' to load this from webbtc.com): ";
	
	$line = trim(fgets(STDIN));
	if ($line == 'x' )
	{
		$inputs = array();
		
		// Loop through inputs:
		foreach ($decoded_transaction['vin'] as $input)
		{
			$get = file_get_contents("http://webbtc.com/tx/{$input['txid']}.hex");
			$dec = RawTransaction::decode($get);
			$pkScript = $dec['vout'][$input['vout']]['scriptPubKey']['hex'];

			$input = array('txid' => $input['txid'],
							  'vout' => $input['vout'],
							  'scriptPubKey' => $pkScript);

			$tx_info = RawTransaction::_get_transaction_type(RawTransaction::_decode_scriptPubKey($pkScript));
			if ($tx_info['hash160'] == $script_hash){
				$input['redeemScript'] = $redeem_script;
			}
				
			$inputs[] = $input;
		}
		unset($input);			unset($tx_info);
		unset($dec);			unset($get);
		
		$json_str = json_encode($inputs);
	}
	else
	{
		$try = @json_decode($line);
		if ( is_object($try) AND count($try) == count($decoded_transaction['vin']) )
		 {
			
			$to_check	= count($try);
			$i			= 0; 
			foreach ($try as &$input)
			{
				if (isset($input->txid) AND $decoded_transaction['vin'][$i]['txid'] == $input->txid
				AND	isset($input->vout) AND $decoded_transaction['vin'][$i]['vout'] == $input->vout
				AND	isset($input->scriptPubKey))
				{
					$tx_info = RawTransaction::_get_transaction_type(RawTransaction::_decode_scriptPubKey($input->scriptPubKey));
					if ($tx_info['hash160'] == $script_hash)
						$input->redeemScript = $redeem_script;

					$to_check--; 
				}
				$i++;
			}
			if($to_check == 0)
				$json_str = json_encode($try);
		}
	}
}


while (!isset($seed))
{
	echo "\nEnter electrum seed or mnemonic: ";
	$line = trim(fgets(STDIN));
	
	if ( ctype_xdigit($line) AND strlen($line) >= 32 )
	{
		$seed = $line;
		continue;
	}
		
	$decode_mnemonic = Electrum::decode_mnemonic($line);
	if (strlen($decode_mnemonic) > 29)
	{
		$seed = $decode_mnemonic;
		continue;
	}
	
	echo "Not a valid seed, or too weak ( < 128 bit)\n\n";
}

echo "Seed accepted.\n\n";

// Learn how many keys we put into the redeem script.
$seed 				= Electrum::stretch_seed($seed);
$seed				= $seed['seed'];
$master_public_key 	= Electrum::generate_mpk($seed);
$private_keys 		= array();
$have_keys			= 0;
$done 				= FALSE;

// Loop until the user is satisfied they have found all keys.
$j 					= 0;
$offset				= 30;
while ($done == FALSE)
{
	$start = $offset*$j;
	echo "Trying keys {$start} to ".($start+$offset)."\n";
	
	// Do public derivation to learn which private keys to derive.
	for ($i = $start; $i < ($start+$offset); $i++)
	{
		$pubkey = Electrum::public_key_from_mpk($master_public_key, $i);
		if (in_array($pubkey, $decode_redeem_script['keys']))
		{
			$private_keys[] = BitcoinLib::private_key_to_WIF(Electrum::generate_private_key($seed, $i),FALSE,'00');
			$have_keys++;
		}
			
		if ($have_keys == $decode_redeem_script['m'])
		{
			$done = TRUE;
			break;
		}
	}
	$j++;
	
	// See if we should continue searching.
	$ask = FALSE;
	if ($done == FALSE)
	{
		echo "Have ".count($private_keys)." private keys we can sign with. Look for more? (y/n) ";
		while ($ask == FALSE) 
		{
			switch(trim(fgets(STDIN)))
			{
				case 'y':
						$ask = TRUE;
						break;
				case 'n':
						$ask = TRUE;
						$done = TRUE;
						break;
				default :
						echo "Please enter y or n :";
						break;
			}
		}
	}
}

// Initialize wallet with known keys.
$wallet = array();
RawTransaction::private_keys_to_wallet($wallet, $private_keys, '00');
RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script), '05');

$sign = RawTransaction::sign($wallet, $raw_transaction, $json_str);
print_r($sign);

