<?php

/**
 * ErrorHandler is a class which is used to handle errors of PHP script.
 * It can handle E_ERROR, E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING,
 * E_USER_NOTICE. To handle E_ERROR this class turns output buffering on by
 * calling ob_start().
 *
 * Here is an example:
 * <code>
 * <?php
 * require_once('es_library/error_handler/error_handler.php');
 * ErrorHandler::SetErrorHandler();
 * echo "Before function!";
 * NotExistencdFunction();
 * echo "After function!";
 * ?>
 * </code>
 *
 * @package SiteManager
 * @author Nikolay Nikolaev <nikolay.nikolaev@eurostudio.net>
 * @copyright 2005-2009 Eurostudio
 * @since 2005-12-09
*/
class ErrorHandler
{
	/**
	 * Contains all collected errors.
	 * @access private
	 * @var array
	 */
	var $_errors;
	/**
	 * Email of the support. This email will be shown on the page if there are
	 * logged and not shown errors.
	 * @access public
	 * @var string
	 */
	var $devEmail;
	/**
	 * Absolute path to the file where collected errors will be saved.
	 * @access public
	 * @var string
	 */
	var $pathToLogFile;
	/**
	 * Error saving level parameter takes on either a bitmask, or named constants.
	 * @access public
	 * @var integer
	 */
	var $saveErrors;
	/**
	 * Error showing level parameter takes on either a bitmask, or named constants.
	 * @access public
	 * @var integer
	 */
	var $showErrors;
	/**
	 * If true context variables ($_GET, $_POST, etc) will be added to log file.
	 * @access public
	 * @var boolean
	 */
	var $logContext;
	/**
	 * Datetime when script was started.
	 * @access public
	 * @var string
	 */
	var $dateTime;
	/**
	 * Timestamp with microseconds when script was started.
	 * @access public
	 * @var integer
	 */
	var $startTime;
	/**
	 * Script execution time in seconds
	 * @access public
	 * @var float
	 */
	var $executionTime;

	/**
	* Is show backtrace
	* @access private
	* @var bool
	*/
	var $showBackTrace;

	/**
	* Is save to log backtrace
	* @access private
	* @var bool
	*/
	var $saveBackTrace;

	/**
	 * Constructor. Sets variables to be used by the class functions.
	 * @access public
	 * @return void
	 */
	function ErrorHandler()
	{
		// Private
		$this->_errors = array();

		// Settings
		$this->devEmail = 'support@eurostudio.net';
		$this->pathToLogFile = dirname(__FILE__).'/../../var/log/error.log';
		$this->saveErrors = E_ALL | E_STRICT; // if no need to save errors - set to false (false, null, 0)

		if (GetFromConfig('DevMode', 'common'))
			$this->showErrors = E_ALL | E_STRICT;
		else
			$this->showErrors = false;

		$this->logContext = false;
		$this->showBackTrace = true;
		$this->saveBackTrace = true;

		// Execution time variables
		$this->dateTime = date("Y-m-d H:i:s (O)");
		$this->startTime = $this->_MicrotimeFloat();
		$this->executionTime = 0;

		// Set error reporting level
		error_reporting(E_ALL | E_STRICT);

		return;
	}

	/**
	 * Creates member of class ErrorHandler if called as static. Sets a user
	 * function (error_handler) to handle errors in a script. Turns output
	 * buffering on and sets output callback function.
	 * @access public
	 * @return boolean
	 */
	static function SetErrorHandler()
	{
		if (isset($this) && get_class($this) == __CLASS__)
		{
			$trace = $this;
		}
		else
		{
			$trace = new ErrorHandler();
		}
		$err = set_error_handler(array(&$trace, '_ErrorHandler'));
		$ob = ob_start(array(&$trace, '_BufferOutputHandler'));

		return $err & $ob;
	}

	/**
	* Invoke an error @see trigger_error but can set any error level
	*
	* @param string $errStr Error message
	* @param int $errNo bit mask of errors default E_USER_ERROR
	*
	* @return void
	*/
	static function TriggerError($errStr, $errNo = E_USER_ERROR)
	{
		$errorTypes = ErrorHandler::GetErrorTypes();
		$errNoStr = isset($errorTypes[$errNo]) ? $errorTypes[$errNo] : $errorTypes[E_USER_ERROR];

		if (substr($errNoStr, 0, 6) == 'E_USER')
		{
			// User level errors can be triggered by trigger_error
			trigger_error($errStr, $errNo);
		}
		else
		{
			// Other error levels we pass in the text of the error
			trigger_error($errNoStr.': '.$errStr);
		}
	}

	/**
	 * Callback function. Saves information about error to internal array.
	 * @access private
	 * @param integer $errNo contains the level of the error raised
	 * @param string $errStr contains the error message
	 * @param string $errFile contains the filename that the error was raised in
	 * @param integer $errLine contains the line number the error was raised at
	 * @return void
	 */
	function _ErrorHandler($errNo, $errStr, $errFile, $errLine)
	{
		if (!($errNo & error_reporting()))
			return;

		$backTrace = array();

		if ($this->showBackTrace || $this->saveBackTrace)
		{
			$backTrace = debug_backtrace();
			array_shift ($backTrace);
		}

		if (count($backTrace))
		{
			// If trigger_error() was called from statement redefine $errFile & $errLine
			if (isset($backTrace[1]['file']) && basename($backTrace[1]['file']) == 'statement.php' && $backTrace[0]['function'] == 'trigger_error')
			{
				for ($i = 1; $i < count($backTrace) - 1; $i++)
				{
					if (isset($backTrace[$i]['file']) && basename($backTrace[$i]['file']) == 'statement.php')
					{
						$errFile = $backTrace[$i + 1]["file"];
						$errLine = $backTrace[$i + 1]["line"];
					}
					else
					{
						break;
					}
				}
			}
			// If trigger_error() was called from self::TriggerError() redefine $errFile & $errLine
			elseif ($backTrace[0]['function'] == 'trigger_error')
			{
				$errFile = $backTrace[1]["file"];
				$errLine = $backTrace[1]["line"];
			}
		}

		// List of error types
		$errorTypes = ErrorHandler::GetErrorTypes();
		// Textual error type
		$errType = @$errorTypes[$errNo];

		// Code below is used to redefine error level if it is passed in error string
		$pattern = "/^(".implode('|', $errorTypes)."):\s(.*)/s";
		$matches = null;
		if (preg_match($pattern, $errStr, $matches))
		{
			$errNo = defined($matches[1]) ? constant($matches[1]) : 0;
			$errStr = $matches[2];

			// Simulate fatal error, it will be catched in self::_BufferOutputHandler();
			if ($errNo == E_ERROR)
			{
				$errorPrepend = ini_get('error_prepend_string');
				$errorAppend = ini_get('error_append_string');
				echo $errorPrepend."<br />\r\n<b>Fatal error</b>:  ".$errStr." in <b>".$errFile."</b> on line <b>".$errLine."</b><br />\r\n".$errorAppend;
				exit();
			}
		}

		// Save error to list
		$this->_errors[] = array(
			'ErrType' => $errType,
			'ErrNo' => $errNo,
			'ErrStr' => $errStr,
			'ErrFile' => $errFile,
			'ErrLine' => $errLine,
			'ErrBackTrace' => ($this->showBackTrace || $this->saveBackTrace ? $backTrace : null)
		);

		return;
	}

	static function GetErrorTypes()
	{
		$errorTypes = array(
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			4096 => "E_TYPE_MISMATCH" // only for 5 php - fatal error
		);

		if (defined('E_STRICT'))
		{
			$errorTypes[E_STRICT] = "E_STRICT";
		}

		return $errorTypes;
	}

	/**
	 * Callback function. Takes output buffer, Checks tail of the buffer on E_ERROR.
	 * In case of no E_ERROR shows content of the buffer and all collected errors.
	 * In case of E_ERROR shows only all collected errors.
	 * @access private
	 * @param string $text output buffer
	 * @return string content
	 */
	function _BufferOutputHandler($text)
	{
		// Regular expression to determine is there was an fatal error
		$errorPrepend = ini_get('error_prepend_string');
		$errorAppend = ini_get('error_append_string');
		$re = '{^(.*)('.
			preg_quote($errorPrepend, '{}')."<br />\r?\n<b>(\w+ error)</b>: \s*".
			'(.*?)'.
			' in <b>)(.*?)(</b>'.
			' on line <b>)(\d+)(</b><br />'.
			"\r?\n".
			preg_quote($errorAppend, '{}').
			')()$'.
			'}s';

		$match = null;
		if (preg_match($re, $text, $match))
			$content = null;
		else
			$content = $text;

		if (is_null($content))
		{
			$this->_ErrorHandler(E_ERROR, $match[4], $match[5], $match[7]);
			$content = '';
		}

		// Count execution time
		$this->executionTime = $this->_MicrotimeFloat() - $this->startTime;

		// Save collected errors to log
		if ($this->saveErrors)
		{
			$this->_SaveLog();
		}

		// Show collected errors to user
		if ($this->showErrors)
		{
			$log = $this->_ShowLog();
			if (!is_null($log))
				$content .= '<hr style="border: 0; height: 1px; color: #000000; background-color: #000000;" />'.$log;
		}

		return $content;
	}

	/**
	 * Generates formatted text with information about all collected errors and
	 * saves it to log file.
	 * @access private
	 * @return void
	 */
	function _SaveLog()
	{
		$errors =& $this->_errors;
		if (count($errors) > 0)
		{
			$errList = '';
			for ($i = 0; $i < count($errors); $i++)
			{
				if (!($errors[$i]['ErrNo'] & $this->saveErrors))
				{
					continue;
				}

				$errList .= "\r\n".$errors[$i]['ErrType'].'['.$errors[$i]['ErrNo'].']: ';
				$errList .= $errors[$i]['ErrStr'].' at '.$errors[$i]['ErrFile'];
				$errList .= ' line '.$errors[$i]['ErrLine']."\r\n";

				if ($this->saveBackTrace && !empty($errors[$i]['ErrBackTrace']) && count($errors[$i]['ErrBackTrace']) > 1)
				{
					$errList .= "\r\nBack trace:\r\n";

					foreach ($errors[$i]['ErrBackTrace'] as $trace)
					{
						if (isset($trace['file']) && isset($trace['line']))
						{
							$errList .= 'File: ' . $trace['file'] . ' ';
							$errList .= 'Line: ' . $trace['line'] . "\r\n";
						}
					}
				}
			}

			if (strlen($errList) > 0)
			{
				$stringToWrite = "----------------------------------------------------------------------\r\n";
				$stringToWrite .= 'STARTED AT: '.$this->dateTime."\r\n";
				$stringToWrite .= 'EXECUTION TIME: '.$this->executionTime."\r\n";

				if (isset($_SERVER['PHP_SELF']))
					$stringToWrite .= 'PHP_SELF: '.$_SERVER['PHP_SELF']."\r\n";

				if ($this->logContext === true)
					$stringToWrite .= $this->_GetContext();

				$stringToWrite .= $errList."\r\n----------------------------------------------------------------------\r\n\r\n\r\n\r\n\r\n";

				if ($fp = fopen($this->pathToLogFile, 'a'))
				{
					fwrite($fp, $stringToWrite);
					fclose($fp);
				}
			}
		}

		return;
	}

	/**
	 * Returns formatted HTML code with information about all collected errors.
	 * @access private
	 * @return string|null
	 */
	function _ShowLog()
	{
		$errors =& $this->_errors;
		if (count($errors) > 0)
		{
			$log = '<font face="Verdana" size="1" color="#999999">STARTED AT: <b>'.$this->dateTime.'</b><br />';
			$log .= 'EXECUTION TIME: <b>'.$this->executionTime.'</b><br />';

			if (isset($_SERVER['PHP_SELF']))
				$log .= 'PHP_SELF: '.$_SERVER['PHP_SELF']."<br /><br />";

			$errList = '';

			for ($i = 0; $i < count($errors); $i++)
			{
				if (!($errors[$i]['ErrNo'] & $this->showErrors))
				{
					continue;
				}

				$errList .= '<b>'.$errors[$i]['ErrType'].'['.$errors[$i]['ErrNo'].']:</b> ';
				$errList .= nl2br($errors[$i]['ErrStr']).' at <b>'.$errors[$i]['ErrFile'].'</b> ';
				$errList .= 'line <b>'.$errors[$i]['ErrLine'].'</b><br /><br />';

				if ($this->showBackTrace && !empty($errors[$i]['ErrBackTrace']) && count($errors[$i]['ErrBackTrace']) > 1)
				{
					$errList .= 'Back trace:<br />';

					foreach ($errors[$i]['ErrBackTrace'] as $trace)
					{
						if (isset($trace['file']) && isset($trace['line']))
						{
							$errList .= 'File: ' . $trace['file'] . ' ';
							$errList .= 'Line: ' . $trace['line'] . '<br />';
						}
					}

					$errList .= '<br />';
				}
			}

			if ($errList == '')
			{
				$log .= '<font face="Verdana" size="1" color="#999999">';
				$log .= 'There was problem during execution script. ';
				$log .= 'Please contact support via email: ';
				$log .= '<a href="mailto: '.$this->devEmail.'">'.$this->devEmail.'</a></font>';
			}
			else
			{
				$log .= $errList;
			}

			$log .= '</font>';
		}
		else
		{
			$log = null;
		}

		return $log;
	}

	/**
	 * Dumps all context variables/arrays ($_GET, $_POST etc) to executable
	 * string and returns it.
	 * @access private
	 * @return string
	 */
	function _GetContext()
	{
		$result = '';
		$contextArr = array(
			'_GET', 'HTTP_GET_VARS',
			'_POST', 'HTTP_POST_VARS',
			'_COOKIE', 'HTTP_COOKIE_VARS',
			'_FILES', 'HTTP_POST_FILES',
			'_ENV', 'HTTP_ENV_VARS',
			'_SESSION', 'HTTP_SESSION_VARS',
			'_REQUEST', '_SERVER');
		for ($i = 0; $i < count($contextArr); $i++)
		{
			if (isset($GLOBALS[$contextArr[$i]]) && is_array($GLOBALS[$contextArr[$i]]))
			{
				$arrStr = '';
				ErrorHandler::_DumpArray($GLOBALS[$contextArr[$i]], $arrStr);
				if (strlen($arrStr) > 0)
				{
					// Remove trailing ','
					if (substr($arrStr, strlen($arrStr) - 1, 1) == ',')
						$arrStr = substr($arrStr, 0, strlen($arrStr) - 1);

					$result .= '$'.$contextArr[$i].' = array('.$arrStr."\r\n);\r\n\r\n";
				}
			}
		}

		return "\r\n---------- START CONTEXT ----------\r\n".$result."---------- END CONTEXT ----------\r\n";
	}

	/**
	 * Dumps array into executable string.
	 * @access private
	 * @param string $data array to dump
	 * @param string $arrStr dumped string
	 * @return void
	 */
	function _DumpArray(&$data, &$arrStr)
	{
		if (is_array($data))
		{
			foreach ($data as $k => $v)
			{
				if (is_array($v))
				{
					$arrStr .= '"\t'.$k.'"=>array(';

					$tempArrStr = '';
					ErrorHandler::_DumpArray($v, $tempArrStr);

					// Remove trailing ','
					if (substr($tempArrStr, strlen($tempArrStr) - 1, 1) == ',')
						$tempArrStr = substr($tempArrStr, 0, strlen($tempArrStr) - 1);

					$arrStr .= $tempArrStr.'),';
				}
				else
				{
					$k = addcslashes($k, "\\\"");
					$v = addcslashes($v, "\\\"");
					$v = str_replace("\r", '\r', $v);
					$v = str_replace("\n", '\n', $v);
					$arrStr .= "\r\n\t\"".$k.'"=>"'.$v.'",';
				}
			}
		}

		return;
	}

	/**
	 * Returns the current Unix timestamp with microseconds.
	 * @access private
	 * @return float timestamp
	 */
	function _MicrotimeFloat()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}
}

?>