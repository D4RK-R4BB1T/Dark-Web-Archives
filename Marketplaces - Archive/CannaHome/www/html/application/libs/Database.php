<?php

/**
 * Class Database
 * Creates a MySQLi database connection. This connection will be passed into the models (so we use
 * the same connection for all models and prevent to open multiple connections at once)
 */
class Database extends MySQLi {
	public	$site_id 	= FALSE;
	public	$site_name	= FALSE;
	public	$site_domain 	= FALSE;
	public	$forum_domain	= FALSE;
	public	$invite_only	= FALSE;
	public	$prefix		= FALSE;
	public 	$lockdown	= FALSE;
	public	$forum		= FALSE;
	public	$m		= FALSE;
	private	$infos		= array();
	/**
	* Construct this Database object, extending the MySQLi object
	*/
	public function __construct(){
		parent::__construct(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
		$this->m = new Memcached();
		$this->m->addServer('localhost', 11211);
		
		$this->getSite();
		
		if (!defined('URL'))
			define('URL', 'http://' . $this->site_domain . '/');
	}
	
	private function _getMemcachedKey(
		$query,
		$types,
		$parameters
	){
		return md5($query . $types . implode(',', $parameters));
	}
	
	private function setMemcachedResult(
		$memcachedKey,
		$result,
		$memcachedExpiry
	){
		return	$this->m->set(
				$memcachedKey,
				$result,
				$memcachedExpiry
			);
	}
	
	private function getMemcachedResult(
		$query,
		$types,
		$parameters,
		&$memcachedKey
	){
		$memcachedKey = $memcachedKey ?: $this->_getMemcachedKey(
			$query,
			$types,
			$parameters
		);
		
		return $this->m->get($memcachedKey);
	}
	
	public function qSelect(
		$query,
		$types = false,
		$parameters = false,
		$debug = false,
		$memcache = false,
		$memcachedExpiry = DATABASE_MEMCACHED_DEFAULT_EXPIRY,
		$memcachedKey = false
	){
		if (
			$memcache &&
			$memcachedResult = $this->getMemcachedResult(
				$query,
				$types,
				$parameters,
				$memcachedKey
			)
		)
			return $memcachedResult;
		
		if ($stmt = $this->prepare($query)){
			if ($types && $parameters){
				$stmt_parameters = array();
				foreach($parameters as $key => $parameter)
					$stmt_parameters[] = &$parameters[$key];
					
				call_user_func_array(
					array($stmt, 'bind_param'),
					array_merge(
						array($types),
						$stmt_parameters
					)
				);
			}
				
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0){
				$variables = array();
				$data = array();
				$meta = $stmt->result_metadata();
				while ($field = $meta->fetch_field())
					$variables[] = &$data[$field->name];
				
				call_user_func_array(array($stmt, 'bind_result'), $variables);
				
				$i = 0;
				while ($stmt->fetch()) {
					foreach ($data as $k=>$v) {
						$array[$i][$k] = $v;
					}
					$i++;
				}
				
				$result = $array;
			} else
				$result = false;
			
			if ($memcache)
				$this->setMemcachedResult(
					$memcachedKey,
					$result,
					$memcachedExpiry
				);
				
			return $result;
		} else
			if ($debug)
				die($this->error);
			else
				throw new Exception($this->error);
	}
	
	public function qQuery($query, $types = false, $parameters = false, $debug = false){
		if( $stmt = $this->prepare($query) ){
			
			if( $types && $parameters )	{
				$stmt_parameters = array();
				foreach($parameters as $key => $parameter)
					$stmt_parameters[] = &$parameters[$key];
				
				call_user_func_array(
					array($stmt, 'bind_param'),
					array_merge(
						array($types),
						$stmt_parameters
					)
				);
			}
			
			if( $stmt->execute() ){
				$str=trim($query);
				switch (strtoupper(substr($str,0,strpos($str,' ')))){
					case 'REPLACE':
					case 'INSERT':
						return $stmt->insert_id ?: $stmt->affected_rows;
					break;
					default:
						return $stmt->affected_rows;
				}
			} else
				if($debug)
					die($this->error);
				else
					throw new Exception($this->error);
		} else
			if($debug)
				die($this->error);
			else
				throw new Exception($this->error);
		
		return false;
	}
	
	public function getSiteInfo(){
		$args = func_get_args();
		
		$array = $attributes = $select = $join = $where = $bind_vars = array();
		$bind_types = '';
		
		foreach ($args as $key => $arg){
			if (isset($this->infos[$arg])) {
				unset($args[$key]);
				$array[$key] = $this->infos[$arg];
				continue;
			} elseif ($siteInfo_cached = $this->m->get('siteInfo-' . $this->site_id . '-' . $arg) ){
				$array[$key] = $siteInfo_cached;
			}
			switch($arg){
				case false:
					break;
				case 'HeaderRegex_Pattern':
					$attributes['HeaderRegex'][] = "`Regex`.`Pattern` AS '".$key."'";
					break;
				case 'HeaderRegex_Substitution':
					$attributes['HeaderRegex'][] = "`Regex`.`Substitution` AS '".$key."'";
					break;
				case 'Stylesheet_CaptchaPage':
					$attributes['Stylesheet_CaptchaPage'][] = "Stylesheet_CaptchaPage.`Stylesheet` AS '".$key."'";
					break;
				case 'Stylesheet_Captcha_First':
					$attributes['Stylesheet_CaptchaPage_First'][] = "Stylesheet_CaptchaPage_First.`Stylesheet` AS '".$key."'";
					break;
				case 'ForumURL':
					$attributes['SiteForum'][] = "CONCAT('" . FORUM_ACCESS_PREFIX . ".','" . $this->accessDomain . "') AS '".$key."'";
					break;
				case 'ForumLink':
					$attributes['SiteForum'][] = "
						IF(
							SiteForum.`ID` IS NULL,
							concat('http://', `Site`.`ForumURL`, '/'),
							IF(
								SiteForum.`ID` = `Site`.`ID`,
								'" . URL . "',
								concat('" . URL . "', 'go/forum/')
							)
						) AS '".$key."'";
					break;
				case 'MainLink':
					$attributes['Site'][] = "CONCAT('http://', `Site`.`Domain`, '/') AS '".$key."'";
					break;
				case 'ServerIdentifier':
					$attributes['Site'][] = "(select conv(COUNT(*) + 10, 10, 36) from Server where ID < @@server_id) AS '".$key."'";
					break;
				default:
					$attributes['Site'][] = "`Site`.`".$arg."` AS '".$key."'";
			}
		}
		
		if( !empty($attributes) ){
			foreach($attributes as $table => $columns){
				switch($table){
					case 'HeaderRegex':
						$join[] = "
							LEFT JOIN	`Regex`
								ON	`Site`.`HeaderRegexID` = `Regex`.`ID`";
					break;
					case 'Stylesheet_CaptchaPage':
						$join[] = "
							LEFT JOIN	`Stylesheet` Stylesheet_CaptchaPage
								ON	`Site`.`StylesheetID_CaptchaPage` = Stylesheet_CaptchaPage.`ID`";
					break;
					case 'Stylesheet_CaptchaPage_First':
						$join[] = "
							LEFT JOIN	`Stylesheet` Stylesheet_CaptchaPage_First
								ON	`Site`.`StylesheetID_CaptchaPage_First` = Stylesheet_CaptchaPage_First.`ID`";
					break;
					case 'SiteForum':
						$join[] = "
							LEFT JOIN	`Site` SiteForum
								ON	(
										SiteForum.`ID` = `Site`.`ID` AND
										SiteForum.`Forum` = TRUE
									) OR
									`Site`.`ForumID` = SiteForum.`ID`
						";
					break;
				}
				foreach($columns as $column){
					$select[] = $column;
				}
			}
			
			$bind_types .= 'i';
			$bind_vars[] = &$this->site_id;
			array_unshift($bind_vars, $bind_types);
			
			if( $stmt_getSiteInfo = $this->prepare("
				SELECT
					" . implode(', ', $select)	. "
				FROM	`Site`
					" . implode(' ', $join)	. "
				WHERE	`Site`.`ID` = ?
			") ){
				
				$stmt_getSiteInfo->bind_param('i', $this->site_id);
				$stmt_getSiteInfo->execute();
				$stmt_getSiteInfo->store_result();
				$variables = array();
				$data = array();
				$meta = $stmt_getSiteInfo->result_metadata();
				while($field = $meta->fetch_field()){
					$variables[] = &$data[$field->name];
				}
				call_user_func_array(array($stmt_getSiteInfo, 'bind_result'), $variables);
				while( $stmt_getSiteInfo->fetch() ) {
					foreach($data as $k=>$v) {
						$array[$k] = $v;
						$this->infos[ $args[$k] ] = $v;
						$this->m->set('siteInfo-' . $this->site_id . '-' . $args[$k], $v, 600);
					}
				}
				
			}
		}
		
		return count($array) == 1 ? $array[0] : $array;
	}
	
	private function getForumDomain($domain){
		if (
			$domains = $this->qSelect(
				"
					SELECT
						`Site`.`Domain`
					FROM
						`Site`
					WHERE
						`ID` = (
							SELECT
								`ForumID`
							FROM
								`Site`
							INNER JOIN
								`Site_Domain` ON
									`Site_Domain`.`SiteID` = `Site`.`ID`
							WHERE
								`Site_Domain`.`Domain` = ?
						)
				",
				's',
				[$domain]
			)
		)
			return $domains[0]['Domain'];
		
		return false;
	}
	
	private function getSite(){
		$this->site_domain = $this->accessDomain = $this->getDomain($this->prefix);
		
		if ($this->prefix){
			define('URL', 'http://' . $this->prefix . '.' . $this->accessDomain . '/');
			if ($this->prefix == FORUM_ACCESS_PREFIX)
				$this->site_domain = $this->getForumDomain($this->site_domain) ?: $this->site_domain;
		}
		
		if ($cached_site = $this->m->get('site-' . $this->site_domain)){
			list(
				$this->site_id,
				$this->site_name,
				$this->invite_only,
				$this->lockdown,
				$this->forum,
				$this->forum_domain,
				$this->private_domain,
				$this->main_domain
			) = $cached_site;
			return TRUE;
		}
		
		$getSite = $this->qSelect(
			"
				SELECT
					`Site`.`ID`,
					`Site`.`Name`,
					`Site`.`InviteOnly`,
					`Site`.`LockDown`,
					`Site`.`Forum`,
					IFNULL(SiteForum.`Domain`, `Site`.`ForumURL`) forumDomain,
					`Site_Domain`.`Type` = 'private' isPrivateDomain,
					IF(
						`Site`.`ForumID` IS NULL,
						(
							SELECT	s2.`Domain`
							FROM	`Site` s2
							WHERE	s2.`ForumID` = `Site`.`ID`
							LIMIT	1
						),
						`Site`.`Domain`
					) Domain
				FROM
					`Site`
				INNER JOIN
					`Site_Domain` ON
						`Site_Domain`.`SiteID` = `Site`.`ID`
				LEFT JOIN
					`Site` SiteForum ON
						`Site`.`ForumID` = SiteForum.`ID`
				WHERE
					`Site_Domain`.`Domain` = ?
			",
			's',
			[$this->site_domain]
		);
		
		$this->site_id		= $getSite[0]['ID'];
		$this->site_name	= $getSite[0]['Name'];
		$this->invite_only	= $getSite[0]['InviteOnly'];
		$this->lockdown		= $getSite[0]['LockDown'];
		$this->forum		= $getSite[0]['Forum'];
		$this->forum_domain	= $getSite[0]['forumDomain'];
		$this->private_domain	= $getSite[0]['isPrivateDomain'];
		$this->main_domain	= $getSite[0]['Domain'];
		
		$this->m->set(
			'site-' . $this->site_domain,
			[
				$this->site_id,
				$this->site_name,
				$this->invite_only,
				$this->lockdown,
				$this->forum,
				$this->forum_domain,
				$this->private_domain,
				$this->main_domain
			],
			30
		);
	}
	
	private function getDomain(&$prefix){
		$exploded = explode('.', $_SERVER['HTTP_HOST']);
		
		$prefix = false;
		if (count($exploded) > 2)
			$prefix = array_shift($exploded);
		
		return implode('.', $exploded);
	}
	
	public function incrementStatistic($statistic, $change = 1){
		if ($this->site_id)
			return $this->qQuery(
				"
					INSERT INTO
						`Site_Statistic` (`SiteID`, `Date`, `Statistic`, `Value`)
					VALUES
						(?, CURDATE(), ?, ?)
					ON DUPLICATE KEY UPDATE
						`Value` = `Value` + ?
				",
				'isii',
				array(
					$this->site_id,
					$statistic,
					$change,
					$change
				)
			);
	}
	public function incrementAccountK($vendor_ID, $change = 1){
		return $this->qQuery(
			"
				INSERT INTO
					`User` (`ID`, `Date`, `Statistic`, `Value`)
				VALUES
					(?, CURDATE(), ?, ?)
				ON DUPLICATE KEY UPDATE
					`Value` = `Value` + ?
			",
			'isii',
			array(
				$this->site_id,
				$statistic,
				$change,
				$change
			)
		);
	}}
