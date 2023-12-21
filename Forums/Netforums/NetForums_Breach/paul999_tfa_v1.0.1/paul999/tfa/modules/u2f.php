<?php
/**
 *
 * 2FA extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 Paul Sohier
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace paul999\tfa\modules;

use paul999\tfa\helper\registration_helper;
use paul999\u2f\AuthenticationResponse;
use paul999\u2f\Exceptions\U2fError;
use paul999\u2f\RegisterRequest;
use paul999\u2f\RegisterResponse;
use paul999\u2f\SignRequest;
use phpbb\db\driver\driver_interface;
use phpbb\exception\http_exception;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;

class u2f extends abstract_module
{
	/**
	 * @var request_interface
	 */
	private $request;

	/**
	 * @var string
	 */
	private $registration_table;

	/**
	 * @var string
	 */
	private $root_path;

	/**
	 * @var \paul999\u2f\U2F
	 */
	private $u2f;

	/**
	 * u2f constructor.
	 * @param driver_interface $db
	 * @param user $user
	 * @param request_interface $request
	 * @param template $template
	 * @param string $registration_table
	 * @param string $root_path
	 */
	public function __construct(driver_interface $db, user $user, request_interface $request, template $template, $registration_table, $root_path)
	{
		$this->db       = $db;
		$this->user     = $user;
		$this->request  = $request;
		$this->template = $template;
		$this->root_path= $root_path;

		$this->registration_table	= $registration_table;
	}

	private function getU2f()
	{
		if (empty($this->u2f))
		{
			$this->u2f = new \paul999\u2f\U2F('https://' . $this->request->server('HTTP_HOST'));
		}
		return $this->u2f;
	}

	/**
	 * Return if this module is enabled by the admin
	 * (And all server requirements are met).
	 *
	 * Do not return false in case a specific user disabeld this module,
	 * OR if the user is unable to use this specific module.
	 * @return boolean
	 */
	public function is_enabled()
	{
		return true;
	}

	/**
	 * Check if the current user is able to use this module.
	 *
	 * This means that the user enabled it in the UCP,
	 * And has it setup up correctly.
	 * This method will be called during login, not during registration/
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function is_usable($user_id)
	{
		if (!$this->is_potentially_usable($user_id))
		{
			return false;
		}
		return $this->check_table_for_user($this->registration_table, $user_id);
	}

	/**
	 * Check if the user can potentially use this.
	 * This method is called at registration page.
	 *
	 * You can, for example, check if the current browser is suitable.
	 *
	 * @param int|boolean $user_id Use false to ignore user
	 * @return bool
	 */
	public function is_potentially_usable($user_id = false)
	{
		$user_agent = strtolower($this->request->server('HTTP_USER_AGENT'));
		return strpos($user_agent, 'edge') === false && strpos($user_agent, 'chrome') !== false && $this->is_ssl();
	}

	/**
	 * Check if the current session is secure.
	 *
	 * @return bool
	 */
	private function is_ssl()
	{
		$secure = $this->request->server('HTTPS');
		if (!empty($secure))
		{
			return 'on' === strtolower($secure) || '1' == $secure;
		}
		else if ('443' == $this->request->server('SERVER_PORT'))
		{
			return true;
		}
		return false;
	}

	/**
	 * Check if the user has any key registered with this module.
	 * There should be no check done if the key is usable, it should
	 * only return if a key is registered.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function key_registered($user_id)
	{
		return $this->check_table_for_user($this->registration_table, $user_id);
	}

	/**
	 * Get the priority for this module.
	 * A lower priority means more chance it gets selected as default option
	 *
	 * There can be only one module with a specific priority!
	 * If there is already a module registered with this priority,
	 * a Exception might be thrown
	 *
	 * @return int
	 */
	public function get_priority()
	{
		return 10;
	}

	/**
	 * Start of the login procedure.
	 * @param int $user_id
	 * @return array
	 * @throws http_exception
	 * @throws U2fError
	 */
	public function login_start($user_id)
	{
		$registrations = json_encode($this->getU2f()->getAuthenticateData($this->getRegistrations($user_id)), JSON_UNESCAPED_SLASHES);

		$sql_ary = array(
			'u2f_request'	=> $registrations
		);

		$count = $this->update_session($sql_ary);

		if ($count != 1)
		{
			// Reset sessions table.
			$sql_ary['u2f_request'] = '';
			$this->update_session($sql_ary);
			throw new http_exception(400, 'TFA_UNABLE_TO_UPDATE_SESSION');
		}
		$this->template->assign_var('U2F_REQ', $registrations);

		return array(
			'S_TFA_INCLUDE_HTML'	=> '@paul999_tfa/tfa_u2f_authenticate.html',
		);
	}

	/**
	 * Actual login procedure
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 * @throws http_exception
	 */
	public function login($user_id)
	{
		$this->user->add_lang_ext('paul999/tfa', 'common');
		try
		{
			$sql = 'SELECT u2f_request 
				FROM ' . SESSIONS_TABLE . " 
				WHERE
					session_id = '" . $this->db->sql_escape($this->user->data['session_id']) . "' AND
					session_user_id = " . (int) $this->user->data['user_id'];
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if (!$row || empty($row['u2f_request']))
			{
				throw new http_exception(403, 'TFA_NO_ACCESS');
			}

			$response = json_decode(htmlspecialchars_decode($this->request->variable('authenticate', '')));

			if (property_exists($response, 'errorCode'))
			{
				if ($response->errorCode == 4) // errorCode 4 means that this device wasn't registered
				{
					throw new http_exception(403, 'TFA_NOT_REGISTERED');
				}
				throw new http_exception(400, 'TFA_SOMETHING_WENT_WRONG');
			}
			$result = new AuthenticationResponse($response->signatureData, $response->clientData, $response->keyHandle); // Do not need to include errorCode, as we already handled it.

			/** @var \paul999\tfa\helper\registration_helper $reg */
			$reg = $this->getU2f()->doAuthenticate($this->convertRequests(json_decode($row['u2f_request'])), $this->getRegistrations($user_id), $result);
			$sql_ary = array(
				'counter' => $reg->getCounter(),
				'last_used' => time(),
			);

			$sql = 'UPDATE ' . $this->registration_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE registration_id = ' . (int) $reg->getId();
			$this->db->sql_query($sql);

			return true;
		}
		catch (U2fError $error)
		{
			$this->createError($error);
		}
		catch (\InvalidArgumentException $invalid)
		{
			throw new http_exception(400, 'TFA_SOMETHING_WENT_WRONG');
		}
		return false;
	}

	/**
	 * @param array $requests
	 * @return array
	 */
	private function convertRequests($requests)
	{
		$result = array();
		foreach ($requests as $request)
		{
			$result[] = new SignRequest($request->challenge, $request->keyHandle, $request->appId);
		}
		return $result;
	}

	/**
	 * Start of registration
	 * @return string
	 * @throws U2fError
	 */
	public function register_start()
	{
		$reg_data = $this->getRegistrations($this->user->data['user_id']);

		$data = $this->getU2f()->getRegisterData($reg_data);

		$sql_ary = array(
			'u2f_request' => json_encode($data[0], JSON_UNESCAPED_SLASHES),
		);

		$count = $this->update_session($sql_ary);

		if ($count != 1)
		{
			// Reset sessions table. We had multiple sessions with same ID!!!
			$sql_ary['u2f_request'] = '';
			$this->update_session($sql_ary);

			trigger_error('TFA_UNABLE_TO_UPDATE_SESSION');
		}

		$this->template->assign_vars(array(
			'U2F_REG'           => true,
			'U2F_SIGN_REQUEST'  => json_encode($data[0], JSON_UNESCAPED_SLASHES),
			'U2F_SIGN'          => json_encode($data[1], JSON_UNESCAPED_SLASHES),
		));

		return 'tfa_u2f_ucp_new';
	}

	/**
	 * Actual registration
	 * @throws http_exception
	 */
	public function register()
	{
		try
		{
			$register = json_decode($this->user->data['u2f_request']);
			$response = json_decode(htmlspecialchars_decode($this->request->variable('register', '')));
			$error = 0;

			if (property_exists($response, 'errorCode'))
			{
				$error = $response->errorCode;
			}

			$registerrequest = new RegisterRequest($register->challenge, $register->appId);
			$responserequest = new RegisterResponse($response->registrationData, $response->clientData, $error);

			$reg = $this->getU2f()->doRegister($registerrequest, $responserequest);

			$sql_ary = array(
				'user_id' => $this->user->data['user_id'],
				'key_handle' => $reg->getKeyHandle(),
				'public_key' => $reg->getPublicKey(),
				'certificate' => $reg->getCertificate(),
				'counter' => ($reg->getCounter() > 0) ? $reg->getCounter() : 0,
				'registered' => time(),
				'last_used' => time(),
			);

			$sql = 'INSERT INTO ' . $this->registration_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);

			$sql_ary = array(
				'u2f_request' => '',
			);

			$this->update_session($sql_ary);
		}
		catch (U2fError $err)
		{
			$this->createError($err);
		}
	}

	/**
	 * This method is called to show the UCP page.
	 * You can assign template variables to the template, or do anything else here.
	 */
	public function show_ucp()
	{
		$this->show_ucp_complete($this->registration_table);
	}

	/**
	 * Delete a specific row from the UCP.
	 * The data is based on the data provided in show_ucp.
	 * @param int $key
	 * @return void
	 */
	public function delete($key)
	{
		$sql = 'DELETE FROM ' . $this->registration_table . '
			WHERE user_id = ' . (int) $this->user->data['user_id'] . '
			AND registration_id =' . (int) $key;

		$this->db->sql_query($sql);
	}

	/**
	 * If this module can add new keys (Or other things)
	 *
	 * @return boolean
	 */
	public function can_register()
	{
		return $this->is_potentially_usable(false);
	}

	/**
	 * Return the name of the current module
	 * This is for internal use only
	 * @return string
	 */
	public function get_name()
	{
		return 'u2f';
	}

	/**
	 * Get a language key for this specific module.
	 * @return string
	 */
	public function get_translatable_name()
	{
		return 'TFA_U2F';
	}

	/**
	 * Select all registration objects from the database
	 * @param integer $user_id
	 * @return array
	 */
	private function getRegistrations($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->registration_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$rows = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$reg = new registration_helper();
			$reg->setCounter($row['counter']);
			$reg->setCertificate($row['certificate']);
			$reg->setKeyHandle($row['key_handle']);
			$reg->setPublicKey($row['public_key']);
			$reg->setId($row['registration_id']);

			$rows[] = $reg;
		}

		$this->db->sql_freeresult($result);
		return $rows;
	}

	/**
	 * @param U2fError $error
	 * @throws http_exception
	 */
	private function createError(U2fError $error)
	{
		switch ($error->getCode())
		{
			/** Error for the authentication message not matching any outstanding
			 * authentication request */
			case U2fError::ERR_NO_MATCHING_REQUEST:
				throw new http_exception(400, 'ERR_NO_MATCHING_REQUEST', array(), $error);

			/** Error for the authentication message not matching any registration */
			case U2fError::ERR_NO_MATCHING_REGISTRATION:
				throw new http_exception(400, 'ERR_NO_MATCHING_REGISTRATION', array(), $error);

			/** Error for the signature on the authentication message not verifying with
			 * the correct key */
			case U2fError::ERR_AUTHENTICATION_FAILURE:
				throw new http_exception(400, 'ERR_AUTHENTICATION_FAILURE', array(), $error);

			/** Error for the challenge in the registration message not matching the
			 * registration challenge */
			case U2fError::ERR_UNMATCHED_CHALLENGE:
				throw new http_exception(400, 'ERR_UNMATCHED_CHALLENGE', array(), $error);

			/** Error for the attestation signature on the registration message not
			 * verifying */
			case U2fError::ERR_ATTESTATION_SIGNATURE:
				throw new http_exception(400, 'ERR_ATTESTATION_SIGNATURE', array(), $error);

			/** Error for the attestation verification not verifying */
			case U2fError::ERR_ATTESTATION_VERIFICATION:
				throw new http_exception(400, 'ERR_ATTESTATION_VERIFICATION', array(), $error);

			/** Error for not getting good random from the system */
			case U2fError::ERR_BAD_RANDOM:
				throw new http_exception(400, 'ERR_BAD_RANDOM', array(), $error);

			/** Error when the counter is lower than expected */
			case U2fError::ERR_COUNTER_TOO_LOW:
				throw new http_exception(400, 'ERR_COUNTER_TOO_LOW', array(), $error);

			/** Error decoding public key */
			case U2fError::ERR_PUBKEY_DECODE:
				throw new http_exception(400, 'ERR_PUBKEY_DECODE', array(), $error);

			/** Error user-agent returned error */
			case U2fError::ERR_BAD_UA_RETURNING:
				throw new http_exception(400, 'ERR_BAD_UA_RETURNING', array(), $error);

			/** Error old OpenSSL version */
			case U2fError::ERR_OLD_OPENSSL:
				throw new http_exception(400, 'ERR_OLD_OPENSSL', array(OPENSSL_VERSION_TEXT), $error);

			default:
				throw new http_exception(400, 'TFA_UNKNOWN_ERROR', array(), $error);
		}
	}

	/**
	 * Update the session with new TFA data
	 * @param $sql_ary
	 * @return int
	 */
	private function update_session($sql_ary)
	{
		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE
				session_id = '" . $this->db->sql_escape($this->user->data['session_id']) . "' AND
				session_user_id = " . (int) $this->user->data['user_id'];
		$this->db->sql_query($sql);

		return $this->db->sql_affectedrows();
	}

}
