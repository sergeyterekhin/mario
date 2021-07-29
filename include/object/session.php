<?php

/**
 * Class Session is used to handle sessions of the users/visitors.
 *
 * @package SiteManager
 * @author Nikolay Nikolaev <nikolay.nikolaev@eurostudio.net>
 * @copyright 2005-2009 Eurostudio
 * @since 2005-12-09
*/
require_once(dirname(__FILE__)."/object.php");

class Session extends BaseObject
{
	/**
	 * Session identifier.
	 * @access private
	 * @var string
	 */
	var $_sessionID;
	/**
	 * Session name.
	 * Under this name script saves session identifier ($_sessionID)
	 * in browser cookie.
	 * @access private
	 * @var string
	 */
	var $_sessionName;
	/**
	 * This variable defines the way data will be saved in cookie.
	 * If it is false, the cookie will expire at the end of the session (when
	 * the browser closes).
	 * @access private
	 * @var boolean
	 */
	var $_inCookie;
	/**
	 * It is true, if session exists in database.
	 * @access private
	 * @var boolean
	 */
	var $_sessionExist;

	/**
	 * Constructor. Sets variables and loads session from database.
	 * @access public
	 * @return void
	 */
	function Session($sessionName)
	{
		$this->_sessionName = $sessionName;

		// By default cookie will expire at the end of the session
		$this->_inCookie = false;

		$this->RemoveExpired();

		$cookie = new BaseObject($_COOKIE);
		$this->_sessionID = $cookie->GetProperty($this->_sessionName);

		$this->_sessionExist = $this->LoadFromDB();
	}

	/**
	 * Destructor. Saves session data to database.
	 * @access public
	 * @return void
	 */
	function SaveToDB($inCookie = null)
	{
		$stmt = GetStatement();
		if (count($this->GetProperties()) > 0)
		{
			if (!is_null($inCookie))
			{
				// We have to overwrite current InCookie field
				if ($inCookie)
					$this->_inCookie = true;
				else
					$this->_inCookie = false;
			}

			$sessionInterval = 60*60*24; // Keep session in database only for 24 hours
			$inCookie = 0;
			if ($this->_inCookie)
			{
				$sessionInterval = 60*60*24*30*COOKIE_EXPIRE;
				$inCookie = 1;
			}

			// Field UserID is needed to determine which sessions to remove when we remove user
			$userID = null;
			$loggedInUser = $this->GetProperty("LoggedInUser");
			if (isset($loggedInUser["UserID"]))
				$userID = $loggedInUser["UserID"];

			$language =& GetLanguage();
			$encoding = $language->GetMySQLEncoding();
			// Switch to utf8 to save serialized array correctly
			if ($encoding != "utf8")
			{
				$stmt->Execute("SET NAMES utf8");
			}

			$a = $this->GetProperties();
			// Convert website encoding to utf8 before save
			if ($encoding != "utf8")
			{
				$this->_Convert($a, "UTF-8", $language->GetHTMLCharset());
			}

			// We have to synchronoze database & cookie session interval
			if ($this->UpdateCookie($sessionInterval))
			{
				$stmt->Execute("INSERT INTO `session`
					(SessionID, InCookie, ExpireDate, SessionData, UserID)
					VALUES (".Connection::GetSQLString($this->_sessionID).",
					".$inCookie.", NOW()+INTERVAL ".$sessionInterval." SECOND,
					".Connection::GetSQLString(serialize($a)).",
					".Connection::GetSQLString($userID).")");
				$this->_sessionExist = true;
			}
			else
			{
				$stmt->Execute("UPDATE `session` SET
					InCookie=".$inCookie.",
					ExpireDate=NOW()+INTERVAL ".$sessionInterval." SECOND,
					SessionData=".Connection::GetSQLString(serialize($a)).",
					UserID=".Connection::GetSQLString($userID)."
					WHERE SessionID=".Connection::GetSQLString($this->_sessionID));
			}

			// Switch to default website encoding
			if ($encoding != "utf8")
			{
				$stmt->Execute("SET NAMES ".$encoding);
			}
		}
		else
		{
			// No need to store empty session
			$stmt->Execute("DELETE FROM `session` WHERE
				SessionID=".Connection::GetSQLString($this->_sessionID));
		}
	}

	/**
	 * Updates session identifier in cookie.
	 * Returns true if session is not exist.
	 * @access private
	 * @return boolean
	 */
	function UpdateCookie($cookieInterval)
	{
		$newSession = false;
		if (!$this->SessionExist())
		{
			srand((float)microtime()*1000000);
			$this->_sessionID = md5(rand());
			// We have to be sure session is not duplicated
			while ($this->LoadFromDB())
			{
				srand((float)microtime()*1000000);
				$this->_sessionID = md5(rand());
			}
			$newSession = true;
		}

		if ($this->_inCookie)
			setcookie($this->_sessionName, $this->_sessionID, time()+$cookieInterval, PROJECT_PATH);
		else
			setcookie($this->_sessionName, $this->_sessionID, 0, PROJECT_PATH);

		return $newSession;
	}

	/**
	 * Updates expire date of the session in database & cookie.
	 * @access public
	 * @return void
	 */
	function UpdateExpireDate()
	{
		if ($this->SessionExist())
		{
			$stmt = GetStatement();

			$sessionInterval = 60*60*24; // Keep session in database only for 24 hours
			if ($this->_inCookie)
				$sessionInterval = 60*60*24*30*COOKIE_EXPIRE;

			// We have to synchronoze database & cookie session interval
			$this->UpdateCookie($sessionInterval);

			$stmt->Execute("UPDATE `session` SET
				ExpireDate=NOW()+INTERVAL ".$sessionInterval." SECOND
				WHERE SessionID=".Connection::GetSQLString($this->_sessionID));
		}
	}

	/**
	 * Loads session data from database.
	 * Returns true if session exists.
	 * @access private
	 * @return boolean
	 */
	function LoadFromDB()
	{
		$result = false;

		$stmt = GetStatement();

		$language =& GetLanguage();
		$encoding = $language->GetMySQLEncoding();
		// Switch to utf8 to load serialized array correctly
		if ($encoding != "utf8")
		{
			$stmt->Execute("SET NAMES utf8");
		}

		$query = "SELECT InCookie, SessionData FROM `session`
			WHERE SessionID=".Connection::GetSQLString($this->_sessionID);

		if ($data = $stmt->FetchRow($query))
		{
			if ($data["InCookie"] > 0)
				$this->_inCookie = true;
			else
				$this->_inCookie = false;

			if ($a = unserialize($data["SessionData"]))
			{
				// Convert utf8 to website encoding
				if ($encoding != "utf8")
				{
					$this->_Convert($a, $language->GetHTMLCharset(), "UTF-8");
				}
				$this->LoadFromArray($a);
			}

			$result = true;
		}

		// Switch to default website encoding
		if ($encoding != "utf8")
		{
			$stmt->Execute("SET NAMES ".$encoding);
		}

		return $result;
	}

	function _Convert(&$array, $to, $from)
	{
		foreach ($array AS $key => $value)
		{
			if (is_array($value))
			{
				$this->_Convert($array[$key], $to, $from);
			}
			else
			{
				if (function_exists('mb_convert_encoding'))
				{
					if (!is_null($array[$key]))
						$array[$key] = mb_convert_encoding($array[$key], $to, $from);
				}
				else
				{
					ErrorHandler::TriggerError("Function mb_convert_encoding() doesn't exist!", E_ERROR);
				}
			}
		}
	}

	/**
	 * Removes expired sessions from database.
	 * @access private
	 * @return void
	 */
	function RemoveExpired()
	{
		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `session` WHERE ExpireDate<NOW()");
	}

	/**
	 * Returns true if session exists in database.
	 * @access public
	 * @return boolean
	 */
	function SessionExist()
	{
		return $this->_sessionExist;
	}
}
?>