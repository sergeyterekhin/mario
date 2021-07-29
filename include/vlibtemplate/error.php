<?php

if (!defined('FATAL'))   define('FATAL', E_USER_ERROR);
if (!defined('WARNING')) define('WARNING', E_USER_WARNING);
if (!defined('NOTICE'))  define('NOTICE', E_USER_NOTICE);
if (!defined('KILL'))    define('KILL', -1); // used for killing inside parsing.

/**
 * Class is used by VLibTemplate.
 * It handles all of the error reporting for VLibTemplate.
 *
 * @package ESLibrary
 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
 * @copyright 2002 Active Fish Group
 * @since 2006-01-11
 */
class VLibTemplateError
{
	function RaiseError($code, $level = null, $extra = null)
	{
		if (!($level & error_reporting()) && $level != KILL)
			return; // binary AND checks for reporting level

		$errorCodes = array(
			'VT_ERROR_NOFILE'              => 'VLibTemplate Error: Template ('.$extra.') file not found.',
			'VT_ERROR_PARSE'               => 'VLibTemplate Error: Parse error!<br>To debug this file, use VLibTemplateDebug instead of VLibTemplate in the class instantiation(i.e. new VLibTemplateDebug).',
			'VT_NOTICE_INVALID_TAG'        => 'VLibTemplate Notice: Invalid tag ('.$extra.').',
			'VT_ERROR_INVALID_TAG'         => 'VLibTemplate Error: Invalid tag ('.$extra.'). To disable this you must turn of the STRICT option.',
			'VT_NOTICE_INVALID_ATT'        => 'VLibTemplate Notice: Invalid attribute ('.$extra.').',
			'VT_WARNING_INVALID_ARR'       => 'VLibTemplate Warning: Invalid loop structure passed to VLibTemplate::SetLoop() (loop name: '.$extra.').',
			'VT_ERROR_INVALID_ERROR_CODE'  => 'VLibTemplate Error: Invalid error raised.',
			'VT_ERROR_WRONG_NO_PARAMS'     => 'VLibTemplate Warning: Wrond parameter count passed to '.$extra.'.',
			'VT_ERROR_UNKNOWN_VAR'         => 'VLibTemplate Error: template var not found.',
			'VT_ERROR_NO_CACHE_WRITE'      => 'VLibTemplate Error: unable to write to cache file ('.$extra.').',
			'VT_ERROR_WRONG_CACHE_TYPE'    => 'VLibTemplate Error: non-directory file found in cache root with same name as directory ('.$extra.').',
			'VT_ERROR_CACHE_MKDIR_FAILURE' => 'VLibTemplate Error: failed to create directory in cache root ('.$extra.').',
			'VT_WARNING_NOT_CACHE_OBJ'     => 'VLibTemplate Warning: called a VLibTemplateCache function ('.$extra.') without instantiating the VLibTemplateCache class.',
			'VT_WARNING_LOOP_NOT_SET'      => 'VLibTemplate Warning: called VLibTemplate::addRow() or VLibTemplate::AddLoop() with an invalid loop name.',
			'VT_WARNING_INVALID_IF_OP'     => 'VLibTemplate Warning: The Operator "'.$extra.'" is not supported by VLibTemplate.',
			'VT_WARNING_NO_LOOP_NAME'      => 'VLibTemplate Warning: You must specify a loop name.'
			);

		$errorLevels = array(
			'VT_ERROR_NOFILE'              => FATAL,
			'VT_ERROR_PARSE'               => FATAL,
			'VT_NOTICE_INVALID_TAG'        => NOTICE,
			'VT_ERROR_INVALID_TAG'         => FATAL,
			'VT_NOTICE_INVALID_ATT'        => NOTICE,
			'VT_WARNING_INVALID_ARR'       => WARNING,
			'VT_ERROR_INVALID_ERROR_CODE'  => FATAL,
			'VT_ERROR_WRONG_NO_PARAMS'     => WARNING,
			'VT_ERROR_UNKNOWN_VAR'         => WARNING,
			'VT_ERROR_NO_CACHE_WRITE'      => KILL,
			'VT_ERROR_WRONG_CACHE_TYPE'    => KILL,
			'VT_ERROR_CACHE_MKDIR_FAILURE' => KILL,
			'VT_WARNING_NOT_CACHE_OBJ'     => WARNING,
			'VT_WARNING_LOOP_NOT_SET'      => WARNING,
			'VT_WARNING_INVALID_IF_OP'     => WARNING,
			'VT_WARNING_NO_LOOP_NAME'      => WARNING
			);

		($level === null) and $level = $errorLevels[$code];
		if ($level == KILL)
		{
			die($errorCodes[$code]);
		}

		if ($msg = $errorCodes[$code])
		{
			trigger_error($msg, $level);
		}
		else
		{
			$level = $errorLevels['VT_ERROR_INVALID_ERROR_CODE'];
			$msg = $errorCodes['VT_ERROR_INVALID_ERROR_CODE'];
			trigger_error($msg, $level);
		}
		return;
	}
}

?>