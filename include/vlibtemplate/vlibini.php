<?php

/**
 * VLibIni is a class used to store configuration parameters
 * for the VLibTemplate class.
 *
 * @package ESLibrary
 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
 * @copyright 2002 Active Fish Group
 * @since 2006-01-11
 */
class VLibIni
{
	/** config vars for VLibTemplate */
	function VLibTemplate()
	{
		return array(
			// Drill depth for tmpl_include's.
			'MAX_INCLUDES' => 10,
			// If set to 1, any variables not found in a loop will search for a global var as well.
			'GLOBAL_VARS' => 1,
			// If set to 1, VLibTemplate will add global vars reflecting the environment.
			'GLOBAL_CONTEXT_VARS' => 1,
			// If set to 1, VLibTemplate will add loop specific vars on each row of the loop.
			'LOOP_CONTEXT_VARS' => 1,
			// Sets a global variable for each top level loops.
			'SET_LOOP_VAR' => 1,
			// 1 of the following: html, url, rawurl, js, sq, dq, none, hex, hexentity.
			'DEFAULT_ESCAPE' => 'html',
			// Dies when encountering an incorrect tmpl_* style tags i.e. tmpl_vae.
			'STRICT' => 1,
			// Removes case sensitivity on all variables.
			'CASELESS' => 0,
			// How to handle unknown variables. One of the following: ignore, remove, leave, print, comment.
			'UNKNOWNS' => 'ignore',
			// Will enable you to time how long VLibTemplate takes to parse your template.
			// You then use the function: GetParseTime()
			'TIME_PARSE' => '0',
			// Will allow template to include a php file using <TMPL_PHPINCLUDE>
			'ENABLE_PHPINCLUDE' => '1',
			// Will allow you to use short tags in your script i.e.: <VAR name="my_var">, <LOOP name="my_loop">...</LOOP>
			'ENABLE_SHORTTAGS' => '0',
			// The following are only used by the VLibTemplateCache class.
			// Directory where the cached filesystem will be set up (full path, and must be writable)
			// with trailing '/'.
			'CACHE_DIR' => VLIB_CACHE_DIR,
			// Duration until file is re-cached in seconds (604800 = 1 week)
			'CACHE_LIFETIME' => 604800,
			// Extention to be used by the cached file i.e. index.php will become index.vtc (VLibTemplate Compiled)
			'CACHE_EXTENSION' => 'vtc'
			);
	}
}

?>