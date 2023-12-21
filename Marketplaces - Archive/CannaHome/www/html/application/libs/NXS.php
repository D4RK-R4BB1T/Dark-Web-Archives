<?php
class NXS {
	public static $BTCxEUR;
	
	public static function get_data($url, $post = false, $proxy = '127.0.0.1:9050') {
		$ch = curl_init();
		$timeout = 50000;
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if($post){
			$fields_string = '';
			
			foreach($post as $key => $value){
				$fields_string .= $key.'='.$value.'&';
			}
			
			rtrim($fields_string, '&');
			
			curl_setopt($ch,CURLOPT_POST, count($post));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		}
		
		if($proxy){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		
		curl_close($ch);
		
		return $data;
	}
	
	public static function addressToScriptHash($address){
		$script =
			'a914' .
			BitcoinLib::base58_decode_checksum($address) .
			'87';
		
		$bs = @pack("H*", $script);
		return RawTransaction::_flip_byte_order(hash("sha256", $bs, false));
	}
	
	public static function partitionNumber(
		$partitionIndex,
		$maxNumber = 360
	){
		if ($partitionIndex == 0)
			return 0;
		
		$cycleElements = 2**floor(log($partitionIndex, 2));	
		$cycleIndex =
			$partitionIndex %
			$cycleElements;
		
		return	(
				self::partitionNumber(
					$cycleIndex,
					$maxNumber
				) +
				$maxNumber /
				$cycleElements /
				2
			) %
			$maxNumber;
	}
	
	public static function sd_square($x, $mean){
		return pow($x - $mean, 2);
	}
	
	public static function removeOutliers(
		$dataset,
		$magnitude = 1
	){
		$count = count($dataset);
		$mean = array_sum($dataset) / $count;
		$deviation =
			sqrt(
				array_sum(
					array_map(
						[
							self,
							'sd_square'
						],
						$dataset,
						array_fill(
							0,
							$count,
							$mean
						)
					)
				) / 
				$count
			) *
			$magnitude;

		return array_filter(
			$dataset,
			function($x) use ($mean, $deviation) {
				return
					$x <= $mean + $deviation &&
					$x >= $mean - $deviation;
			}
		);
	}
	
	public static function getFilteredAverage($dataset){
		$filteredDataset = self::removeOutliers($dataset);
		
		return array_sum($filteredDataset) / count($filteredDataset);
	}
	
	public static function getImageData($url, $saveto = false, $proxy = '127.0.0.1:9050'){
		$ch = curl_init ($url);
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		
		if($proxy){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		}
		
		$raw=curl_exec($ch);
		curl_close ($ch);
		
		if( $saveto ){
			if(file_exists($saveto)){
				unlink($saveto);
			}
			$fp = fopen($saveto,'x');
			fwrite($fp, $raw);
			fclose($fp);		
		} else {
			return $raw;
		}
	}
	
	public static function cachedQuery($url, $cache_interval = 60){
		$m	= new Memcached();
		$m->addServer('localhost', 11211);
		
		if( $cachedRequest = $m->get('request-' . $url) )
			return $cachedRequest;
		else {
			$content = self::get_data($url);
			
			$m->set('request-' . $url, $content, $cache_interval);
			
			return $content;
		}
	}
	
	/*public static function giveReputation($user_object, $db_object, $user_id, $reputation_change, $activity = false, $reputation_required = REPUTATION_AFFECT_REPUTATION){
		
		$stmt_checkActivityLogging = $db_object->prepare("
			SELECT
				`LogActivities`
			FROM
				`User`
			WHERE
				`ID` = ?
		");
		
		$stmt_insertActivity = $db_object->prepare("
			INSERT INTO
				`Activity` (`UserID`, `Content`)
			VALUES
				(?, ?)
		");
		
		$stmt_updateReputation = $db_object->prepare("
			UPDATE
				`User`
			SET
				`Reputation` = IF(`Reputation` + ? < 0, 0, `Reputation` + ?)
			WHERE
				`ID` = ?
			AND	`Reputation` >= 0
		");
		
		if( false !== $stmt_checkActivityLogging && false !== $stmt_insertActivity && false !== $stmt_updateReputation ){
			
			$stmt_checkActivityLogging->bind_param('i', $user_id);
			$stmt_checkActivityLogging->execute();
			$stmt_checkActivityLogging->store_result();
			
			if( $stmt_checkActivityLogging->num_rows == 1 ){
			
				$stmt_checkActivityLogging->bind_result($activity_logging);
				$stmt_checkActivityLogging->fetch();
				
				if( $activity && !empty($activity_logging) ){
					
					$rsa = new RSA();
					
					$activity = array_merge($activity, array('ReputationChange' => $user_object->Reputation >= $reputation_required ? $reputation_change : 0) );
					
					$encrypted_activity = $rsa->qEncrypt( json_encode($activity), $user_object->Info($user_id, 'PublicKey') );
					
					$stmt_insertActivity->bind_param('is', $user_id, $encrypted_activity);
					$stmt_insertActivity->execute();
					
				}
				
				if( $user_object->Reputation >= $reputation_required ){
					
					$stmt_updateReputation->bind_param('iii', $reputation_change, $reputation_change, $user_id);
					$stmt_updateReputation->execute();
					
				}
				
				return true;
				
				
			} else {
				
				return false;
				
			}
			
		}
		
	}*/
	
	public static function sendMessage($user_object, $db_object, $content, $recipient_id, $sender_id = false, $timeout_days = 99){
		if( !$sender_id ){
			$sender_id = $user_object->ID == $recipient_id ? SYSTEM_MESSAGER_ID : $user_object->ID;
		}
		
		$rsa = new RSA();
		
		list($encrypt_pgp, $pgp) = $user_object->Info(0, $recipient_id, 'EncryptPGP', 'PGP'); 
		
		if( $encrypt_pgp == 1 && !empty($pgp) ){
			$pgp = new PGP($pgp);
			
			$content = '[pgp]' . $pgp->qEncrypt($content, true) . '[/pgp]';
		}
		
		$content = self::formatText($content);
		
		$public_key = $user_object->Info(0, $recipient_id, 'PublicKey');
		
		$encrypted_message = $rsa->qEncrypt(
			json_encode(
				array(
					'Date'		=> date('j F Y'),
					'Timestamp'	=> time(),
					'Message'	=> $content
				)
			),
			$public_key
		);
			
		if( $stmt_insertMessage = $db_object->prepare("
			INSERT INTO
				`Message` (
					`SenderID`,
					`RecipientID`,
					`Content`,
					`AutoDelete`,
					`Sent`
				)
			VALUES (
				?,
				?,
				?,
				NOW() + INTERVAL " . $timeout_days . " DAY,
				NOW()
			)
		") ){
			$stmt_insertMessage->bind_param('iis', $sender_id, $recipient_id, $encrypted_message);
			
			if( $stmt_insertMessage->execute() ){
				return true;	
			}
		}
	}
	
	public static function timestampTimezoneFormat(
		$timestamp,
		$format = 'j F Y - g:ia T',
		$timezone = DEFAULT_TIMEZONE
	){
		$date = new DateTime("@$timestamp");
		$date->setTimeZone(new DateTimeZone($timezone));
		
		$formattedDate = $date->format($format);
		
		return $formattedDate;
	}
	
	public static function timestampSplitDateTime(
		$timestamp,
		$dateFormat,
		$timeFormat,
		$timezone = DEFAULT_TIMEZONE
	){
		$date = self::timestampTimezoneFormat(
			$timestamp,
			$dateFormat,
			$timezone
		);
		$time = self::timestampTimezoneFormat(
			$timestamp,
			$timeFormat,
			$timezone
		);
		
		return [
			$date,
			$time	
		];
	}
	
	public static function convertCurrencies($db, $value, $from_curr_id, $to_curr_id){	
		if( $stmt_convertCurrency = $db->prepare("
			SELECT
				(
					SELECT	`1EUR`
					FROM	`Currency`
					WHERE	`ID` = ?
				),
				(
					SELECT	`1EUR`
					FROM	`Currency`
					WHERE	`ID` = ?
				)
		") ){
			
			$stmt_convertCurrency->bind_param('ii', $from_curr_id, $to_curr_id);
			$stmt_convertCurrency->execute();
			$stmt_convertCurrency->store_result();
			$stmt_convertCurrency->bind_result($from_curr_1eur, $to_curr_1eur);
			$stmt_convertCurrency->fetch();
			
			$value_in_eur = $value / $from_curr_1eur;
			$value_in_curr = $value_in_eur * $to_curr_1eur;
			
			return $value_in_curr;
			
		}
		
	}
	
	public static function formatText(
		$input,
		$db = FALSE,
		&$dialog = NULL,
		$noAnchorModals = FALSE,
		$contentEditable = FALSE
	){
		if (preg_match(REGEX_PGP_MESSAGE_ONLY, $input)){
			return self::formatText(
				'[pgp]' . $input . '[/pgp]',
				$db,
				$dialog,
				$noAnchorModals,
				$contentEditable
			);
		} elseif (preg_match(REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT, $input)){
			// HAS SUBJECT FIRST THEN PGP MESSAGE
			$input = preg_replace(
				REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT_SEARCH,
				REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT_REPLACE,
				$input
			);
			
			return self::formatText(
				$input,
				$db,
				$dialog,
				$noAnchorModals,
				$contentEditable
			);
		}
		
		$input = preg_replace(
			'/^> (.+)/m',
			'[quote]$1[/quote]',
			$input
		);
		
		// Strip Tags
		$input = htmlspecialchars($input);
		
		// PGP Blocks
		if (preg_match_all('/\[pgp\]((?:(?!\[\/pgp).)*)\[\/pgp]/is', $input, $comment_pgps) > 0){
			$comment_pgp_blocks = array();
			
			foreach( $comment_pgps[1] as $comment_pgp ){
				$comment_pgp_blocks[] = '</p><pre' . ($contentEditable ? ' contentEditable' : FALSE) . '>' . $comment_pgp . '</pre><p>';
			}
			
			$input = preg_replace('/\[pgp\](?:(?!\[\/pgp).)*\[\/pgp]/is', '%%%PGPBLOCK%%%', $input);
		}
		
		// Misc Elements
		$input = str_replace(
			'[sp]',
			'&emsp;',
			$input
		);
		$input = str_replace(
			'[br]',
			PHP_EOL,
			$input
		);
		
		// filter deprecated [a]
		$input = preg_replace_callback(
			'/\[a=([^\]]+)\]((?:(?!\[\/a).)*)\[\/a]/i',
			function ($matches){
				$URL = $matches[1];
				$content = $matches[2];
				
				if (substr($URL, 0, 1) == '/')
					$URL = URL . substr($URL, 1);
				
				if ($URL !== $content)
					return $URL . ' ' . $content;
					
				return $URL;
			},
			$input
		);
		
		if (
			strpos(
				$input,
				'[a='
			) === false
		)
			$input = preg_replace(
				REGEX_HYPERLINK,
				REGEX_HYPERLINK_REPLACE,
				$input
			); // Hyperlinks without [a]
		
		$uniqid = uniqid();
		$link_count = 1;
		$modals = array();	
		$input = preg_replace_callback(
			'/\[a=([^\]]+)\]((?:(?!\[\/a).)*)\[\/a]/i',
			function($matches) use (&$link_count, &$modals, &$uniqid, $db){
				$URL = str_replace(
					[
						'"',
						"'"
					],
					'',
					strip_tags($matches[1])
				);
				$content = $matches[2];
				
				$accessDomain = substr(URL, 7, -1);
				
				// Remove prefix
				$exploded = explode('.', $accessDomain);
				if (count($exploded) > 2)
					$accessDomain = implode('.', array_slice($exploded, 1));
				
				if (
					$isForumRedirect =
						!$db &&
						preg_match(
							'/^http:\/\/' . FORUM_ACCESS_PREFIX . '\.' . $accessDomain . '(.*)$/',
							$URL,
							$redirectMatches
						)
				)
					return '<a target="_blank" href="/go/forum' . $redirectMatches[1] . '">' . str_replace('http://' . FORUM_ACCESS_PREFIX . '.' . $accessDomain, 'forum', $content) . '</a>';
				
				if (
					$isMarketRedirect =
						$db &&
						preg_match(
							'/^http:\/\/(?:' . $accessDomain . '|' . $db->main_domain . ')(.*)$/',
							$URL,
							$redirectMatches
						)
				)
					return '<a target="_blank" href="/go/market' . $redirectMatches[1] . '">' . str_replace(['http://' . $accessDomain, 'http://' . $db->main_domain], 'market', $content) . '</a>';
				
				if (
					(
						$isNotExternal =
							substr($URL, 0, 1) == '/' ||
							substr($URL, 0, strlen(URL)) == URL ||
							(
								$db &&
								substr($URL, 7, strlen(FORUM_ACCESS_PREFIX) + strlen($db->main_domain) + 1) == FORUM_ACCESS_PREFIX . '.' . $db->main_domain
							)
					) ||
					(
						isset($_SESSION['isAdmin']) &&
						$_SESSION['isAdmin']
					)
				)
					return '<a' . ($isNotExternal ? false : ' target="_blank"') . ' href="' . str_replace([URL, 'http://' . FORUM_ACCESS_PREFIX . '.' . $db->main_domain . '/'], '/', $URL) . '">' . str_replace([URL, 'http://' . FORUM_ACCESS_PREFIX . '.' . $db->main_domain . '/'], '/', $content) . '</a>';
				elseif (substr($URL, 0, 4) == 'http') {
					$link_id = $uniqid . '_' . $link_count++;
					$modals[] = '<input id="anchor-' . $link_id . '" hidden type="checkbox"><div class="modal"><label for="anchor-' . $link_id . '"></label>
									<div class="rows-10">
										<label class="close" for="anchor-' . $link_id . '">&times;</label>
										<p class="row">This link points to an external webpage: <em>' . $URL . '</em>.<br>Are you sure you wish to continue?</p>
										<div class="row cols-10">
											<div class="col-6"><a target="_blank" href="' . $URL . '" class="btn wide color">Continue</a></div>
											<div class="col-6"><label for="anchor-' . $link_id . '" class="btn wide red color">Nevermind</label></div>
										</div>
									</div>
								</div>';
					return '<label class="a-like" for="anchor-' . $link_id . '">' . $content . '</label>';
				} else
					return $content;
			},
			$input
		); // [a] to <a>
		
		$input = preg_replace_callback(
			'/\[color=([^\]]+)\]((?:(?!\[\/color).)*)\[\/color]/i',
			function($matches){
				$color = strip_tags($matches[1]);
				$content = $matches[2];
				
				return '<span style="color:' . $color . '">' . $content . '</span>';
			},
			$input
		); // [color] to <span style="color:$1">
		
		$input = preg_replace_callback(
			'/\[size=(\d{2,3})\]((?:(?!\[\/size).)*)\[\/size]/i',
			function($matches){
				$size = strip_tags($matches[1]);
				$content = $matches[2];
				
				if ($size >= 20 && $size <= 200)
					return '<span style="font-size:' . $size . '%">' . $content . '</span>';
			},
			$input
		); // [size] to <span style="font-size:$1">
		
		if ($db){
			if( preg_match_all("/@([\w\-_]+)/", $input, $comment_replies) > 0 ){
				
				$dialog = TRUE;
				
				if( $stmt_getRepliedCommenter = $db->prepare("
					SELECT
						`Alias`
					FROM
						`User`
					WHERE
						`Alias` = ?
				") ){
					$replied_commenter = array();
					
					foreach( $comment_replies[1] as $replied_alias ){
						
						$stmt_getRepliedCommenter->bind_param('s', $replied_alias);
						$stmt_getRepliedCommenter->execute();
						$stmt_getRepliedCommenter->store_result();
						$stmt_getRepliedCommenter->bind_result($pretty_replied_alias);
						$stmt_getRepliedCommenter->fetch();
						
						if( $stmt_getRepliedCommenter->num_rows == 1 ){
						
							$replied_commenter[$replied_alias] = array(
								'alias' => $pretty_replied_alias
							);
							
						} else
							$replied_commenter[$replied_alias] = false;
					}
					
					$input = preg_replace_callback(
						"/@([\w\-_]+)/",
						function($matches) use ($replied_commenter){
							if( $replied_commenter[ $matches[1] ] ){
								return " <a class='reply-to' href='/u/" . $matches[1] . "/'>" . $replied_commenter[ $matches[1] ]['alias'] . "</a>";
							} else
								return $matches[0];
						}, $input
					);
						
				}
				
			}
		}
		
		$input = preg_replace('/\[b\]((?:(?!\[\/b).)*)\[\/b]/is', '<strong>$1</strong>', $input); // [b] to <strong>
		$input = preg_replace('/\[i\]((?:(?!\[\/i).)*)\[\/i]/is', '<em>$1</em>', $input); // [i] to <em>
		$input = preg_replace('/\[s\]((?:(?!\[\/s).)*)\[\/s]/i', '<s>$1</s>', $input); // [s] to <s>
		$input = preg_replace('/\[u\]((?:(?!\[\/u).)*)\[\/u]/i', '<u>$1</u>', $input); // [u] to <u>
		$input = preg_replace('/\[small\]((?:(?!\[\/small).)*)\[\/small]/i', '<small>$1</small>', $input); // [s] to <s>
		
		// Quotes
		if (preg_match_all('/\[quote\]((?:(?!\[\/quote).)*)\[\/quote]/is', $input, $comment_quotes_simple) > 0){
			$comment_block_quotes = [];
			
			foreach ($comment_quotes_simple[1] as $comment_quote_simple){
				$comment_block_quotes[] = '</p><blockquote>' . $comment_quote_simple . '</blockquote><p>';
			}
			
			$input = preg_replace('/\[quote\](?:(?!\[\/quote).)*\[\/quote]/is', '%%%BLOCKQUOTE%%%', $input);
		}
		
		// UL LIsts
		if (preg_match_all('/(?:\n?^[-*>]+[^\n]+){2,}/m', $input, $comment_uls) > 0){
			$comment_ul_lists = array();
			
			$input = preg_replace_callback(
				'/(?:\n?^[-*>]+[^\n]+){2,}/m',
				function($matches) use (&$comment_ul_lists){
					$list = preg_replace(
						'/^[-*>]\s?([^\n]+)/m',
						'<li>$1</li>',
						$matches[0]
					);
					$comment_ul_lists[] = '</p><ul>' . $list . '</ul><p>';
					
					return '%%%UL-LIST%%%';		
				},
				$input
			);
		}
		
		// OL Lists
		if (preg_match_all('/(?:\n?^\d+[\.)]+\s+[^\n]+){2,}/m', $input, $comment_ols) > 0){
			$comment_ol_lists = array();
			
			$input = preg_replace_callback(
				'/(?:\n?^\d+[\.)]+\s+[^\n]+){2,}/m',
				function($matches) use (&$comment_ol_lists){
					$list = preg_replace(
						'/^\d+[\.)]+\s+([^\n]+)/m',
						'<li>$1</li>',
						$matches[0]
					);
					$comment_ol_lists[] = '</p><ol>' . $list . '</ol><p>';
					
					return '%%%OL-LIST%%%';
				},
				$input
			);
		}
		
		$input = preg_replace('/\[hr\]/is', '%%%HORIZONTAL-LINE%%%', $input); // Horizontal Lines
		
		$input = self::nl2p($input);
		
		$dialog = FALSE;
		
		if ($db){ // Only on forum
			// Adding Quotes
			if( preg_match_all("/\[quote=['\"]?(\d+)['\"]?/i", $input, $comment_quotes) > 0){
				
				$dialog = TRUE;
				
				if( $stmt_getQuotedComment = $db->prepare("
					SELECT
						IF (
							`Discussion_Comment`.`Anonymous`,
							'Anonymous',
							`User`.`Alias`
						),
						`Discussion_Comment`.`Anonymous`
					FROM
						`Discussion_Comment`
					INNER JOIN `User`
						ON	`Discussion_Comment`.`PosterID` = `User`.`ID`
					WHERE
						`Discussion_Comment`.`ID` = ?
				") ){
				
					$quoted_commenter = array();
					
					foreach( $comment_quotes[1] as $quoted_comment_id ){
						
						$stmt_getQuotedComment->bind_param('i', $quoted_comment_id);
						$stmt_getQuotedComment->execute();
						$stmt_getQuotedComment->store_result();
						$stmt_getQuotedComment->bind_result($quoted_commenter_alias, $quoted_commenter_anonymous);
						$stmt_getQuotedComment->fetch();
						
						if( $stmt_getQuotedComment->num_rows == 1 )
							$quoted_commenter[$quoted_comment_id] = array(
								'alias' => $quoted_commenter_alias,
								'anonymous' => $quoted_commenter_anonymous
							);
						else
							$quoted_commenter[$quoted_comment_id] = false;
					}
						
					$input = preg_replace_callback(
						"/\[quote=['\"]?(\d+)['\"]? date=['\"]?(\d{4}-(?:1[0-2]|0?[1-9])-(?:[1-3][0-9]|0?[1-9]))['\"]?\]((?:(?!\[\/quote).)*)\[\/quote\]/is",
						function($matches) use ($quoted_commenter){
							if( $quoted_commenter[ $matches[1] ] ){
								return "</p><div class='quote'><a href='/comment/" . $matches[1] . "/' class='btn'>View</a><p class='quoted'>Posted on <strong>" . date('j F Y', strtotime($matches[2])) . "</strong> by " . ($quoted_commenter[$matches[1]]['anonymous'] ? '<em>' : '<a href="/u/' . $quoted_commenter[$matches[1]]['alias'] . '/">' ) . $quoted_commenter[ $matches[1] ]['alias'] . ($quoted_commenter[$matches[1]]['anonymous'] ? '</em>' : '</a>') . " :</p>" . self::nl2p(preg_replace('/^(?:<br ?\/?>\s*)+/i', '', trim($matches[3]))) . "</div><p>";
							} else {
								return "</p><div class='quote'><p><strong>DELETED COMMENT</strong></p></div><p>";
							}
						}, $input
					);		
				}	
			}
			
			$input = preg_replace('/\[spoiler\]((?:(?!\[\/spoiler).)*)\[\/spoiler]/i', '<span class="spoiler">$1</span>', $input); // spoiler tags
		}
		
		if( !empty($comment_block_quotes) ){
			for( $i = 0; $i < count($comment_block_quotes) ; $i++ ){
				$input = preg_replace('/%%%BLOCKQUOTE%%%/', $comment_block_quotes[$i], $input, 1);
			}
		}
		
		// Re-insert Lists
		if( !empty($comment_ul_lists) ){
			for( $i = 0; $i < count($comment_ul_lists) ; $i++ ){
				$input = preg_replace('/%%%UL-LIST%%%/', $comment_ul_lists[$i], $input, 1);
			}
		}
		if( !empty($comment_ol_lists) ){
			for( $i = 0; $i < count($comment_ol_lists) ; $i++ ){
				$input = preg_replace('/%%%OL-LIST%%%/', $comment_ol_lists[$i], $input, 1);
			}
		}
		
		
		// Re-inserting PGP Blocks
		if( !empty($comment_pgp_blocks) ){
			for( $i = 0; $i < count($comment_pgp_blocks) ; $i++ ){
				
				$input = preg_replace('/%%%PGPBLOCK%%%/', $comment_pgp_blocks[$i], $input, 1);
				
			}
		}
		
		// Re-insert horizontal lines
		$input = preg_replace('/%%%HORIZONTAL-LINE%%%/', '</p><hr><p>', $input);
		
		// Removing Empty Paragraphs and Double Linebreaks
		$input = preg_replace('/<p>(?:\s|<br ?\/?>)*<\/p>|(?:<br ?\/?>){2,}/i', '', $input);
		/*$input = preg_replace('/>\s*<br ?\/?>/i', '>', $input);*/
		$input = preg_replace('/\/p>\s*(?:<br ?\/?>)+/i', '/p>', $input);
		$input = preg_replace('/(?:<br ?\/?>)\s*<\/p/i', '</p', $input);
		$input = preg_replace('/<p>\s*(?:<br ?\/?>)+/i', '<p>', $input);
		
		// Insert Modals
		foreach($modals as $modal)
			$input = $input . $modal;
		
		return $input;
	}
	
	public static function nl2p($string){
		return '<p>' . preg_replace('#(?:<br>[\r\n]*){2,}#', '</p><p>', str_replace(PHP_EOL, '<br>' , $string)) . '</p>';
	}
	
	public static function getExcerpt($content, $maxLength = FALSE){
		$start = strpos($content, '<p>');
		$end = strpos($content, '</p>');
		$excerpt = substr(
			$content,
			$start+3,
			$end-$start-3
		);
		$excerpt =
			$maxLength && strlen($excerpt) > $maxLength
				? substr($excerpt, 0, $maxLength) . '&hellip;'
				: $excerpt;
				
		return $excerpt;
	}
	
	public static function roundSignificant($number, $sigdigs) {
		if($number == 0 || $number < 0)
			return 0;
		
		$multiplier = 1; 
		while ($number < 0.1) { 
			$number *= 10; 
			$multiplier /= 10; 
		} 
		while ($number >= 1) { 
			$number /= 10; 
			$multiplier *= 10; 
		} 
		return round($number, $sigdigs) * $multiplier; 
	}
	
	public static function compareFloatNumbers(
		$float1,
		$float2,
		$operator='=',
		$epsilon = 0.000000001
	){  
		$float1 = (float)$float1;  
		$float2 = (float)$float2;  
		  
		switch ($operator)  
		{  
			// equal  
			case "=":  
			case "eq":  
			{  
				if (abs($float1 - $float2) < $epsilon) {  
					return true;  
				}  
				break;    
			}  
			// less than  
			case "<":  
			case "lt":  
			{  
				if (abs($float1 - $float2) < $epsilon) {  
					return false;  
				}  
				else  
				{  
					if ($float1 < $float2) {  
						return true;  
					}  
				}  
				break;    
			}  
			// less than or equal  
			case "<=":  
			case "lte":  
			{  
				if (self::compareFloatNumbers($float1, $float2, '<') || self::compareFloatNumbers($float1, $float2, '=')) {  
					return true;  
				}  
				break;    
			}  
			// greater than  
			case ">":  
			case "gt":  
			{  
				if (abs($float1 - $float2) < $epsilon) {  
					return false;  
				}  
				else  
				{  
					if ($float1 > $float2) {  
						return true;  
					}  
				}  
				break;    
			}  
			// greater than or equal  
			case ">=":  
			case "gte":  
			{  
				if (self::compareFloatNumbers($float1, $float2, '>') || self::compareFloatNumbers($float1, $float2, '=')) {  
					return true;  
				}  
				break;    
			}  
			case "<>":  
			case "!=":  
			case "ne":  
			{  
				if (abs($float1 - $float2) > $epsilon) {  
					return true;  
				}  
				break;    
			}  
			default:  
			{  
				die("Unknown operator '".$operator."' in compareFloatNumbers()");     
			}  
		}  
		  
		return false;  
	}
	
	public static function getPrefix() {
		$exploded = explode('.', $_SERVER['HTTP_HOST']);
		if (count($exploded) > 2)
			return $exploded[0];
		else
			return false;
	}
	
	public static function reduceCategories($category_id, $recursive_categories, &$all_categories = false){
		$new_category = false;
		
		foreach($recursive_categories as $category){
			if( !$new_category && $category['ID'] == $category_id ){
				if( array_key_exists('Children', $category) ){
					$new_category = array(
						'ID' => $category_id,
						'Name' => $category['Name'],
						'Children' => $category['Children']
					);
					if( !$all_categories ) break;
				} else {
					$new_category = array(
						'ID' => $category_id,
						'Name' => $category['Name']
					);
					if( !$all_categories ) break;
				}
			} elseif( !$new_category && array_key_exists('Children', $category) ){
				if( $new_category = self::reduceCategories($category_id, $category['Children'], $all_categories) )
					if( !$all_categories ) break;
				else {
					unset($all_categories[ $category['ID'] ] );
					continue;
				}
				
			} else {
				unset($all_categories[ $category['ID'] ] );
				continue;
			}
		}
		
		return $new_category;
	}
	
	public static function linearArray($arr){
		$values = array($arr['ID']);
		
		if($arr && array_key_exists('Children', $arr) ){
			foreach( $arr['Children'] as $child ){
				$values = array_merge($values, self::linearArray($child));
			}
		}
		return $values;
	}
	
	public static function roundUpToMultiple($number, $multiple){
		return
			round($number)%$multiple === 0
				? round($number)
				: round(
					($number + $multiple/2) / $multiple
				) * $multiple;
	}
	
	public static function roundFloat($float, $decimals = 4){
		$float = (float)$float;
		
		return floor($float * (10**$decimals)) / (10**$decimals);
	}
	
	public static function filterCategory($category_id, $categories){
		foreach( $categories as $key => $category ){
			if(
				$category['ID'] == $category_id ||
				(
					array_key_exists('Children', $category) &&
					self::filterCategory(
						$category_id,
						$category['Children']
					)
				)
			)
				return $category_id;
		}
	
		return false;
	}
	
	public static function notifyExternally($db, $user_id, $content){
		return;
		
		$stmt_getUserAddresses = $db->prepare("
			SELECT
				`ExternalNotifications`,
				`PGP`
			FROM
				`User`
			WHERE
				`ID` = ?
			AND	`PGP` IS NOT NULL
		");
		
		$stmt_insertPendingNotification = $db->prepare("
			INSERT INTO
				`PendingNotification` (`UserID`, `Content`)
			VALUES
				(?, ?)
		");
		
		if( false !== $stmt_getUserAddresses && false !== $stmt_insertPendingNotification ){
			
			$stmt_getUserAddresses->bind_param('i', $user_id);
			$stmt_getUserAddresses->execute();
			$stmt_getUserAddresses->store_result();
			$stmt_getUserAddresses->bind_result($notification_mode, $pgp);
			$stmt_getUserAddresses->fetch();
			
			if( $notification_mode == 'none' )
				return true;
				
			if( $content['type'] == 'new_message' && $notification_mode == 'orders' )
				return true;
				
			$pgp = new PGP($pgp);
			
			$encrypted_message = $pgp->qEncrypt($content['message']);
			
			$stmt_insertPendingNotification->bind_param('is', $user_id, $encrypted_message);
			
			if( $stmt_insertPendingNotification->execute() )
				return true;
		}
	}
	
	public static function makeRecursive($d, $r = 0, $pk = 'ParentID', $k = 'ID', $c = 'Children') {
		$m = array();
		foreach ($d as $id => $e) {
			isset($m[$e[$pk]]) ? false : $m[$e[$pk]] = array();
			isset($m[$e[$k]]) ? false : $m[$e[$k]] = array();
			$m[$e[$pk]][$id] = array_merge($e, array($c => &$m[$e[$k]]));
		}
		
		return $m[$r]; // add [0] if there could only be one root node
	}
	
	public static function tallyCount($categories, $count_index = 'ListingCount', $children_index = 'Children'){
		foreach($categories as $key => $category){
			if( array_key_exists($children_index, $category) && !empty($category[$children_index]) ){
				$categories[$key][$count_index] = $categories[$key][$count_index] + self::getTotalCount($category[$children_index], $count_index, $children_index);
				
				if($categories[$key][$count_index] == 0){
					unset($categories[$key]);
				} else {
					$categories[$key][$children_index] = self::tallyCount($categories[$key][$children_index], $count_index, $children_index);
				}
			} elseif($categories[$key][$count_index] == 0)
				unset($categories[$key]);
		}
		
		return $categories;
	}
	
	public static function getTotalCount($categories, $count_index = 'ListingCount', $children_index = 'Children'){
		$count = 0;
		
		foreach($categories as $category){
			
			$count = $count + $category[$count_index];
			
			if( array_key_exists($children_index, $category) && !empty($category[$children_index]) ){
				$count = $count + self::getTotalCount($category[$children_index], $count_index);
			}
			
		}
		
		return $count;
	}
	
	public static function getB36($decimal, $offset = 1295){
		return base_convert($decimal + $offset, 10, 36);
	}
	
	public static function getDecimal($base_36, $offset = 1295){
		return base_convert($base_36, 36, 10) - $offset;
	}
	
	public static function parseMinutes($minutes){
		if ($minutes <= 0)
			return '0 minutes';
		
		if ($minutes > 1440)
			return	ceil($minutes/1440) .
				' days';
		
		if ($minutes > 60)
			return	ceil($minutes/60) .
				' hours';
		
		return	floor($minutes) .
			' minutes';
	}
	
	public static function getBTCxEUR($db){
		if(!self::$BTCxEUR) {
			
			$stmt_1EURtoBTC = $db->prepare("
				SELECT
					`1EUR`
				FROM
					`Currency`
				WHERE
					`ID` = 1
				LIMIT 1
			");
			
			$stmt_1EURtoBTC->execute();
			$stmt_1EURtoBTC->store_result();
			$stmt_1EURtoBTC->bind_result(self::$BTCxEUR);
			$stmt_1EURtoBTC->fetch();
			
		}
		
		return self::$BTCxEUR;
	}
	
	public static function decimalToFraction($decimal){
		switch($decimal){
			case 0.25:
				return '&frac14;';
			break;
			case 0.5:
				return '&frac12;';
			break;
			case 0.75:
				return '&frac34;';
			break;
			default:
				return FALSE;
		}
	}
	
	public static function formatDecimal(
		$value,
		$decimals = DEFAULT_DECIMALS,
		$decimalSeparator = DEFAULT_DECIMAL_SEPARATOR,
		$thousandsSeparator = DEFAULT_THOUSANDS_SEPARATOR,
		$clearTrailingZeroes = TRUE,
		$outputFractions = FALSE
	){
		if (
			$outputFractions &&
			$fraction = self::decimalToFraction($value)
		)
			return $fraction;
		
		$formattedValue = number_format(
			$value,
			$decimals,
			$decimalSeparator,
			$thousandsSeparator
		);
		
		if($clearTrailingZeroes){
			if ($clearTrailingZeroes > 1)
				$formattedValue = rtrim(
					rtrim(
						$formattedValue,
						'0'
					),
					$decimalSeparator
				);
			else
				$formattedValue =
					substr($formattedValue, -1*$decimals) == $decimalSeparator . str_repeat('0', $decimals)
						? number_format(
							$value,
							0,
							$decimalSeparator,
							$thousandsSeparator
						)
						: $formattedValue;
		}
			
		return $formattedValue;
	}
	
	public static function formatPrice($currency, $price, $sig_digs = FALSE){
		$precise_value = $price*$currency['XEUR'];
		$value = $sig_digs ? self::roundSignificant($precise_value, $sig_digs) : $precise_value;
		
		$formattedValue = self::formatDecimal(
			$value,
			2,
			'.',
			','
		);
		
		return !empty($currency['Symbol']) ? $currency['Symbol'] . $formattedValue : $formattedValue.' '.$currency['ISO'];
	}
	
	public static function formatNumber($integer){
		switch($integer){
			case 1:
				return 'one';
			case 2:
				return 'two';
			case 3:
				return 'three';
			case 4:
				return 'four';
			case 5:
				return 'five';
			case 6:
				return 'six';
			case 7:
				return 'seven';
			case 8:
				return 'eight';
			case 9:
				return 'nine';
			default:
				return $integer;
		}
	}
	
	public static function showError($error_code = 404){
		header('Location: ' . URL . 'error/' . $error_code . '/');
		die;
	}
	
	public static function getPictureVariant($image, $suffix = IMAGE_THUMBNAIL_SUFFIX){
		if(!$image)
			return FALSE;
		
		$imageArr = explode('.', $image);
		$image = $imageArr[0] . $suffix . '.' . $imageArr[1];
		return $image;
	}
	
	public static function getSegwitRedeemScript($publicKey){
		return '0014' . BitcoinLib::hash160($publicKey);
	}
	
	public static function deriveBIP32PublicKey(
		$keyID,
		$bip32Public,
		$derivationPrefix = '0/'
	){
		return BIP32::extract_public_key(BIP32::build_key(array($bip32Public, 'M'), $derivationPrefix . $keyID));
	}
	
	public static function getBIP32Address(
		$keyID,
		$bip32Public,
		$prefixPublic = CRYPTOCURRENCIES_PREFIX_PUBLIC_DEFAULT,
		$prefixBIP49 = '05',
		$segwit = false,
		$derivationPrefix = '0/'
	){
		$derived_public_key = self::deriveBIP32PublicKey($keyID, $bip32Public, $derivationPrefix);
		
		if (
			$segwit ||
			substr($bip32Public, 0, 4) == 'ypub'
		)
			$address = BitcoinLib::public_key_to_address(
				self::getSegwitRedeemScript($derived_public_key),
				$prefixBIP49
			);
		elseif (substr($bip32Public, 0, 4) == 'zpub'){
			switch ($prefixPublic){
				case CRYPTOCURRENCIES_PREFIX_PUBLIC_BITCOIN:
					$HRP = CRYPTOCURRENCIES_HRPS['BTC'];
					break;
				case CRYPTOCURRENCIES_PREFIX_PUBLIC_LITECOIN:
					$HRP = CRYPTOCURRENCIES_HRPS['LTC'];
					break;
			}
			
			$cryptocurrency = new Cryptocurrency(
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				$HRP
			);
			
			$address = $cryptocurrency->bech32EncodePublicKey($derived_public_key);
		}
		
		else
			$address = BitcoinLib::public_key_to_address($derived_public_key, $prefixPublic);
		
		return $address;
	}
	
	public static function validateXPUB($xpub){
		try {
			$key = BIP32::import($xpub);
		} catch(Exception $e) {
			return false;
		}
		
		return true;
	}
	
	public function validateCryptocurrencyPublicKey($publicKey){
		return
			!empty($publicKey) &&
			BitcoinLib::validate_public_key($publicKey) &&
			!preg_match('/\s/', $publicKey) &&
			strlen($publicKey) >= 10;
	}
	
	public static function generateAuthenticationPGPMessage($PGP, $sessionName, $siteName, $siteDomain, $userAlias, &$answer = NULL){
		$authenticationCode = $answer = $_SESSION[$sessionName]['answer'] = substr(hexdec(hash('sha256', uniqid(openssl_random_pseudo_bytes(16), TRUE))), 2, 10);
		
		try {
			$message = $PGP->qEncrypt(
				'Your authentication code is: '.$authenticationCode . PHP_EOL . PHP_EOL .
				'This message was generated as part of the PGP authentication process on ' . $siteName . ' for user \'' . $userAlias . '\'.' . PHP_EOL . PHP_EOL .
				'Please ensure that the URL in the address bar is: ' . $siteDomain,
				TRUE
			);
		} catch (Exception $e) {
			return false;
		}
		
		$_SESSION[$sessionName]['message'] = $message;
		
		return $message;
	}
	
	public static function getByteLength($string) {
		return dechex(strlen($string) / 2);
	}
	
	public static function getOffset($itemCount, $itemsPerPage, $page, &$invalidPage = FALSE){
		if( ceil($itemCount/$itemsPerPage) < $page ){
			$offset = 0;
			
			$invalidPage = TRUE;
		} else
			$offset = $itemsPerPage*($page - 1);
			
		return $offset;
	}
	
	public static function generateRandomString(
		$length = 10,
		$caseSensitive = FALSE,
		$secure = FALSE
	) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		if($caseSensitive) $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$nextCharacter = $secure
				? $characters[random_int(0, $charactersLength - 1)]
				: $characters[rand(0, $charactersLength - 1)];
			$randomString .= $nextCharacter;
		}
		return $randomString;
	}
	
	public static function factorial($number){ 
		$factorial = 1;
		for ($i = 1; $i <= $number; $i++)
			$factorial = $factorial * $i;
			
		return $factorial; 
	}
	
	public static function nCr($n, $r){
		return self::factorial($n) / (self::factorial($r) * self::factorial($n - $r));
	}
	
	public static function enumerateCombinations($objects, $sample){
		$combinations = [];
		$combinationCount = self::nCr($objects, $sample);
		
		$elements = range(1, $objects);
		
		while (count($combinations) < $combinationCount){
			$randomCombination = array_map(
				function ($key){
					return $key + 1;
				},
				array_rand($elements, $sample)
			);
			asort($randomCombination);
			
			$combinationsKey = implode('-', $randomCombination);
			
			if (!isset($combinations[$combinationsKey]))
				$combinations[$combinationsKey] = $randomCombination;
		}
		
		asort($combinations);
		
		return array_values($combinations);
	}
}
