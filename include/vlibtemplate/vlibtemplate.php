<?php

require_once(dirname(__FILE__).'/error.php');
if (SM_PHP_MODE == 5)
	require_once(dirname(__FILE__).'/vlibini5.php');
else
	require_once(dirname(__FILE__).'/vlibini.php');

/**
 * VLibTemplate is a class used to seperate PHP and HTML.
 *
 * @package ESLibrary
 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
 * @copyright 2002 Active Fish Group
 * @since 2006-01-11
 */
class VLibTemplate
{
	/**
	 * Contains options of the class.
	 * @access public
	 * @var array
	 */
	var $options = array(
		'MAX_INCLUDES'        => 10,
		'GLOBAL_VARS'         => null,
		'GLOBAL_CONTEXT_VARS' => null,
		'LOOP_CONTEXT_VARS'   => null,
		'SET_LOOP_VAR'        => null,
		'DEFAULT_ESCAPE'      => null,
		'STRICT'              => null,
		'CASELESS'            => null,
		'UNKNOWNS'            => null,
		'TIME_PARSE'          => null,
		'ENABLE_PHPINCLUDE'   => null,
		'ENABLE_SHORTTAGS'    => null,
		'INCLUDE_PATHS'       => array(),
		'CACHE_DIR'           => null,
		'CACHE_LIFETIME'      => null,
		'CACHE_EXTENSION'     => null);

	/**
	 * Open and close tags used for escaping.
	 * @access public
	 * @var array
	 */
	var $escapeTags = array(
		'html'      => array('open' => 'htmlspecialchars(', 'close' => ', ENT_QUOTES)'),
		'url'       => array('open' => 'urlencode(', 'close' => ')'),
		'rawurl'    => array('open' => 'rawurlencode(', 'close' => ')'),
		'js'        => array('open' => '$this->_EscapeJS(', 'close' => ")"),
		'sq'        => array('open' => 'addcslashes(', 'close' => ", \"'\")"),
		'dq'        => array('open' => 'addcslashes(', 'close' => ", '\"')"),
		'1'         => array('open' => 'htmlspecialchars(', 'close' => ', ENT_QUOTES)'),
		'0'         => array('open' => '', 'close' => ''),
		'none'      => array('open' => '', 'close' => ''),
		'hex'       => array('open' => '$this->_EscapeHex(', 'close' => ', false)'),
		'hexentity' => array('open' => '$this->_EscapeHex(', 'close'=> ', true)'),
		'nl2br'     => array('open' => 'nl2br(htmlspecialchars(', 'close' => ', ENT_QUOTES))'));

	/**
	 * Open and close tags used for formatting.
	 * @access public
	 * @var array
	 */
	var $formatTags = array(
		'uc'        => array('open' => 'strtoupper(', 'close'=> ')'),
		'lc'        => array('open' => 'strtolower(', 'close'=> ')'),
		'ucfirst'   => array('open' => 'ucfirst(', 'close'=> ')'),
		'lcucfirst' => array('open' => 'ucfirst(strtolower(', 'close'=> '))'),
		'ucwords'   => array('open' => 'ucwords(', 'close'=> ')'),
		'lcucwords' => array('open' => 'ucwords(strtolower(', 'close'=> '))'));

	/**
	 * Operators allowed when using extended TMPL_IF syntax.
	 * @access public
	 * @var array
	 */
	var $allowedIfOps = array('==','!=','<>','<','>','<=','>=','%2==','%3==','%4==');

	/**
	 * Root directory of VLibTemplate automagically filled in
	 * @access public
	 * @var string
	 */
	var $vLibTemplateRoot = null;

	/** Contains current directory used when doing recursive include */
	var $_currentIncludeDir = array();

	/** Current depth of includes */
	var $_includeDepth = 0;

	/** Full path to tmpl file */
	var $_tmplFileName = null;

	/** File data before it's parsed */
	var $_tmplFile = null;

	/** Parsed version of file, ready for eval()ing */
	var $_tmplFileP = null;

	/** eval()ed version ready for printing or whatever */
	var $_tmplOutput = null;

	/** Array for variables to be kept */
	var $_vars = array();

	/** Array where loop variables are kept */
	var $_arrVars = array();

	/** Array which holds the current namespace during parse */
	var $_nameSpace = array();

	/** Variable is set to true once the template is parsed, to save re-parsing everything */
	var $_parsed = false;

	/** Array holds all unknowns vars */
	var $_unknowns = array();

	/** Microtime when template parsing began */
	var $_firstParseTime = null;

	/** Total time taken to parse template */
	var $_totalParseTime = null;

	/** Name of current loop being passed in */
	var $_currLoopName = null;

	/** Rows with the above loop */
	var $_currLoop = array();

	/** Script URL path of currently PHP script */
	var $_scriptURL = '';

	/** Define vars to avoid warnings */
	var $_debug = null;
	var $_cache = null;

	/**
	 * Usually called by the class constructor.
	 * Stores the filename in $this->_tmplFileName.
	 * Raises an error if the template file is not found.
	 * @access public
	 * @param string $tmplFile full path to template file
	 * @return boolean true
	 */
	function NewTemplate($tmplFile)
	{
		if (!$tfile = $this->_FileSearch($tmplFile))
			VLibTemplateError::RaiseError('VT_ERROR_NOFILE', KILL, $tmplFile);

		// make sure that any parsing vars are cleared for the new template
		$this->_tmplFile = null;
		$this->_tmplFileP = null;
		$this->_tmplOutput = null;
		$this->_parsed = false;
		$this->_unknowns = array();
		$this->_firstParseTime = null;
		$this->_totalParseTime = null;

		// reset debug module
		if ($this->_debug) $this->_DebugReset();

		$this->_tmplFileName = $tfile;
		return true;
	}

	/**
	 * Sets variables to be used by the template
	 * If $k is an array, then it will treat it as an associative array
	 * using the keys as variable names and the values as variable values.
	 * @access public
	 * @param mixed $k key to define variable name
	 * @param mixed $v variable to assign to $k
	 * @return boolean true/false
	 */
	function SetVar($k, $v = null)
	{
		if (is_array($k))
		{
			foreach($k as $key => $value)
			{
				$key = ($this->options['CASELESS']) ? strtolower(trim($key)) : trim($key);
				if (preg_match('/^[A-Za-z_]+[A-Za-z0-9_-]*$/', $key) && $value !== null )
				{
					$this->_vars[$key] = $value;
				}
			}
		}
		else
		{
			if (preg_match('/^[A-Za-z_]+[A-Za-z0-9_-]*$/', $k) && $v !== null)
			{
				if ($this->options['CASELESS'])
					$k = strtolower($k);
				$this->_vars[trim($k)] = $v;
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Unsets a variable which has already been set
	 * Parse in all vars wanted for deletion in seperate parametres
	 * @access public
	 * @param string var name to remove use: VLibTemplate::UnsetVar(var[, var..])
	 * @return boolean true/false returns true unless called with 0 params
	 */
	function UnsetVar()
	{
		$numArgs = func_num_args();
		if ($numArgs < 1)
			return false;

		for ($i = 0; $i < $numArgs; $i++)
		{
			$var = func_get_arg($i);
			if ($this->options['CASELESS'])
				$var = strtolower($var);
			if (!preg_match('/^[A-Za-z_]+[A-Za-z0-9_-]*$/', $var))
				continue;
			unset($this->_vars[$var]);
		}
		return true;
	}

	/**
	 * Gets all vars currently set in global namespace.
	 * @access public
	 * @return array
	 */
	function GetVars()
	{
		if (empty($this->_vars))
			return false;
		return $this->_vars;
	}

	/**
	 * Gets a single var from the global namespace
	 * @access public
	 * @return var
	 */
	function GetVar($var)
	{
		if ($this->options['CASELESS'])
			$var = strtolower($var);
		if (empty($var) || !isset($this->_vars[$var]))
			return false;
		return $this->_vars[$var];
	}

	/**
	 * Sets the GLOBAL_CONTEXT_VARS
	 * @access public
	 * @return true
	 */
	function SetContextVars()
	{
		$_phpself = @$GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'];
		$_pathinfo = @$GLOBALS['HTTP_SERVER_VARS']['PATH_INFO'];
		$_request_uri = @$GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'];
		$_qs = @$GLOBALS['HTTP_SERVER_VARS']['QUERY_STRING'];

		// the following fixes bug of $PHP_SELF on Win32 CGI and IIS.
		$_self = (!empty($_pathinfo)) ? $_pathinfo : $_phpself;
		$_uri = (!empty($_request_uri)) ? $_request_uri : $_self.'?'.$_qs;

		$this->SetVar('__SELF__', $_self);
		$this->SetVar('__REQUEST_URI__', $_uri);
		return true;
	}

	/**
	 * Builds the loop construct for use with <TMPL_LOOP>.
	 * @access public
	 * @param string $k string to define loop name
	 * @param array $v array to assign to $k
	 * @return boolean true/false
	 */
	function SetLoop($k, $v)
	{
		if (is_array($v) && preg_match('/^[A-Za-z_]+[A-Za-z0-9_-]*$/', $k))
		{
			$k = ($this->options['CASELESS']) ? strtolower(trim($k)) : trim($k);
			$this->_arrVars[$k] = array();
			if ($this->options['SET_LOOP_VAR'] && !empty($v))
				$this->SetVar($k, 1);
			if (($this->_arrVars[$k] = $this->_ArrayBuild($v)) === false)
			{
				VLibTemplateError::RaiseError('VT_WARNING_INVALID_ARR', WARNING, $k);
			}
		}
		return true;
	}

	/**
	 * Sets the name for the curent loop in the 3 step loop process.
	 * @access public
	 * @param string $name string to define loop name
	 * @return boolean true/false
	 */
	function NewLoop($loopname)
	{
		if (preg_match('/^[a-z_]+[a-z0-9_]*$/i', $loopname))
		{
			$this->_currLoopName[$loopname] = $loopname;
			$this->_currLoop[$loopname] = array();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Adds a row to the current loop in the 3 step loop process.
	 * @access public
	 * @param array $row loop row to add to current loop
	 * @param string $loopname loop to which you want to add row, if not set will use last loop set using NewLoop().
	 * @return boolean true/false
	 */
	function AddRow($row, $loopname = null)
	{
		if (!$loopname)
			$loopname = end($this->_currLoopName);

		if (!isset($this->_currLoop[$loopname]) || empty($this->_currLoopName))
		{
			VLibTemplateError::RaiseError('VT_WARNING_LOOP_NOT_SET', WARNING);
			return false;
		}
		if (is_array($row))
		{
			$this->_currLoop[$loopname][] = $row;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Completes the 3 step loop process. This assigns the rows and resets
	 * the variables used.
	 * @access public
	 * @param string $loopname to commit loop. If not set, will use last loopname set using NewLoop()
	 * @return boolean true/false
	 */
	function AddLoop($loopname = null)
	{
		if ($loopname == null)
		{
			// add last loop used
			if (!empty($this->_currLoop))
			{
				foreach ($this->_currLoop as $k => $v)
				{
					$this->SetLoop($k, $v);
					unset($this->_currLoop[$k]);
				}
				$this->_currLoopName = array();
				return true;
			}
			else
			{
				return false;
			}
		}
		elseif (!isset($this->_currLoop[$loopname]) || empty($this->_currLoopName))
		{
			// NewLoop not yet envoked
			VLibTemplateError::RaiseError('VT_WARNING_LOOP_NOT_SET', WARNING);
			return false;
		}
		else
		{
			// add a specific loop
			$this->SetLoop($loopname, $this->_currLoop[$loopname]);
			unset($this->_currLoopName[$loopname], $this->_currLoop[$loopname]);
		}
		return true;
	}

	/**
	 * Use this function to return the loop structure. This is useful in setting
	 * inner loops using the 3-step-loop process.
	 * @access public
	 * @param string $loopname name of loop to get
	 * @return boolean true/false
	 */
	function GetLoop($loopname = null)
	{
		if (!$loopname)
			$loopname = end($this->_currLoopName);

		if (!isset($this->_currLoop[$loopname]) || empty($this->_currLoopName))
		{
			VLibTemplateError::RaiseError('VT_WARNING_LOOP_NOT_SET', WARNING);
			return false;
		}

		$loop = $this->_currLoop[$loopname];
		unset($this->_currLoopName[$loopname], $this->_currLoop[$loopname]);
		return $loop;
	}

	/**
	 * Unsets a loop which has already been set.
	 * Can only unset top level loops.
	 * @access public
	 * @param string loop to remove use: VLibTemplate::UnsetLoop(loop[, loop..])
	 * @return boolean true/false returns true unless called with 0 params
	 */
	function UnsetLoop()
	{
		$numArgs = func_num_args();
		if ($numArgs < 1)
			return false;

		for ($i = 0; $i < $numArgs; $i++)
		{
			$var = func_get_arg($i);
			if ($this->options['CASELESS'])
				$var = strtolower($var);
			if (!preg_match('/^[A-Za-z_]+[A-Za-z0-9_-]*$/', $var))
				continue;
			unset($this->_arrVars[$var]);
		}
		return true;
	}


	/**
	 * Resets the VLibTemplate object. After using VLibTemplate::Reset() you must
	 * use VLibTemplate::NewTemplate(tmpl) to reuse, not passing in the options array.
	 * @access public
	 * @return boolean true
	 */
	function Reset()
	{
		$this->ClearVars();
		$this->ClearLoops();
		$this->_tmplFileName = null;
		$this->_tmplFile = null;
		$this->_tmplFileP = null;
		$this->_tmplOutput = null;
		$this->_parsed = false;
		$this->_unknowns = array();
		$this->_firstParseTime = null;
		$this->_totalParseTime = null;
		$this->_currLoopName = null;
		$this->_currLoop = array();
		return true;
	}

	/**
	 * Unsets all variables in the template
	 * @access public
	 * @return boolean true
	 */
	function ClearVars()
	{
		$this->_vars = array();
		return true;
	}

	/**
	 * Unsets all loops in the template
	 * @access public
	 * @return boolean true
	 */
	function ClearLoops()
	{
		$this->_arrVars = array();
		$this->_currLoopName = null;
		$this->_currLoop = array();
		return true;
	}

	/**
	 * Unsets all variables and loops set using SetVar/Loop()
	 * @access public
	 * @return boolean true
	 */
	function ClearAll()
	{
		$this->ClearVars();
		$this->ClearLoops();
		return true;
	}

	/**
	 * Returns true if unknowns were found after parsing.
	 * Function MUST be called AFTER one of the parsing functions to have any relevance.
	 * @access public
	 * @return boolean true/false
	 */
	function UnknownsExist()
	{
		return (!empty($this->_unknowns));
	}

	/**
	 * Alias for UnknownsExist.
	 * @access public
	 */
	function Unknowns()
	{
		return $this->UnknownsExist();
	}

	/**
	 * Returns an array of all unknown vars found when parsing.
	 * This function is only relevant after parsing a document.
	 * @access public
	 * @return array
	 */
	function GetUnknowns()
	{
		return $this->_unknowns;
	}

	/**
	 * Sets how you want to handle variables that were found in the
	 * template but not set in VLibTemplate using VLibTemplate::SetVar().
	 * @access public
	 * @param  string $arg ignore, remove, print, leave or comment
	 * @return boolean
	 */
	function SetUnknowns($arg)
	{
		$arg = strtolower(trim($arg));
		if (preg_match('/^ignore|remove|print|leave|comment$/', $arg))
		{
			$this->options['UNKNOWNS'] = $arg;
			return true;
		}
		return false;
	}

	/**
	 * Function sets the paths to use when including files.
	 * Use of this function: VLibTemplate::SetPath(string path [, string path, ..]);
	 * i.e. if $tmpl is your template object do: $tmpl->SetPath('/web/htdocs/templates','/web/htdocs/www');
	 * with as many paths as you like.
	 * if this function is called without any arguments, it will just delete any previously set paths.
	 * @access public
	 * @param string path (mulitple)
	 * @return bool success
	 */
	function SetPath($path = array())
	{
		if (is_array($path) && count($path) > 0)
		{
			foreach ($path as $k => $v)
			{
				array_push($this->options['INCLUDE_PATHS'], $v);
			}
		}
		else
		{
			$this->options['INCLUDE_PATHS'] = array();
		}
		return true;
	}

	/**
	 * After using one of the parse functions, this will allow you
	 * access the time taken to parse the template.
	 * see OPTION 'TIME_PARSE'.
	 * @access public
	 * @return float time taken to parse template
	 */
	function GetParseTime()
	{
		if ($this->options['TIME_PARSE'] && $this->_parsed)
		{
			return $this->_totalParseTime;
		}
		return false;
	}

	/**
	 * Identical to PParse() except that it uses output buffering w/ gz compression thus
	 * printing the output directly and compressed if poss.
	 * Will possibly if parsing a huge template.
	 * @access public
	 * @return boolean true/false
	 */
	function FastPrint()
	{
		$ret = $this->_Parse('ob_gzhandler');
		print($this->_tmplOutput);
		return $ret;
	}

	/**
	 * Calls parse, and then prints out $this->_tmplOutput
	 * @access public
	 * @return boolean true/false
	 */
	function PParse()
	{
		if (!$this->_parsed)
			$this->_Parse();
		print($this->_tmplOutput);
		return true;
	}

	/**
	 * Alias for PParse()
	 * @access public
	 */
	function PPrint()
	{
		return $this->PParse();
	}

	/**
	 * Returns the parsed output, ready for printing, passing to mail() ...etc.
	 * Invokes $this->_Parse() if template has not yet been parsed.
	 * @access public
	 * @return boolean true/false
	 */
	function Grab()
	{
		if (!$this->_parsed)
			$this->_Parse();
		return $this->_tmplOutput;
	}

	/**
	 * VLibTemplate constructor.
	 * if $tmplFile has been passed to it, it will send to $this->NewTemplate()
	 * @access private
	 * @param string $tmplFile full path to template file
	 * @param array $options see above
	 * @return boolean true/false
	 */
	function VLibTemplate($tmplFile = null, $options = null)
	{
		if (is_array($tmplFile) && $options == null)
		{
			$options = $tmplFile;
			unset($tmplFile);
		}

		$this->vLibTemplateRoot = dirname(realpath(__FILE__));

		if (is_array(VLibIni::VLibTemplate()))
		{
			foreach (VLibIni::VLibTemplate() as $name => $val)
			{
				$this->options[$name] = $val;
			}
		}

		if (is_array($options))
		{
			foreach($options as $key => $val)
			{
				$key = strtoupper($key);
				if ($key == 'INCLUDE_PATHS')
				{
					$this->SetPath($val);
				}
				else
				{
					$this->_SetOption($key, strtolower($val));
				}
			}
		}

		if (isset($tmplFile))
			$this->NewTemplate($tmplFile);
		if ($this->options['GLOBAL_CONTEXT_VARS'])
			$this->SetContextVars();
		return true;
	}

	/**
	 * Function returns the text from the file, or if we're using cache, the text
	 * from the cache file. MUST RETURN DATA.
	 * @access private
	 * @param string tmplfile contains path to template file
	 * @param do_eval used for included files. If set then this function must do the eval()'ing.
	 * @return mixed data/string or boolean
	 */
	function _GetData($tmplFile, $doEval = false)
	{
		// check the current file depth
		if ($this->_includeDepth > $this->options['MAX_INCLUDES'] || $tmplFile == false)
		{
			return;
		}
		else
		{
			if ($this->_debug)
				array_push($this->_debugIncludedFiles, $tmplFile);
			if ($doEval)
			{
				array_push($this->_currentIncludeDir, dirname($tmplFile));
				$this->_includeDepth++;
			}
		}


		if($this->_cache && $this->_checkCache($tmplFile))
		{
			// cache exists so lets use it
			$data = fread($fp = fopen($this->_cacheFile, 'r'), filesize($this->_cacheFile));
			fclose($fp);
		}
		else
		{
			// no cache lets parse the file
			$data = fread($fp = fopen($tmplFile, 'r'), filesize($tmplFile));
			fclose($fp);

			$regex = '/(<|<\/|{|{\/|<!--|<!--\/){1}\s*';
			$regex.= '(?:tmpl_)';
			if ($this->options['ENABLE_SHORTTAGS'])
				$regex.= '?'; // makes the TMPL_ bit optional
			$regex.= '(var|if|elseif|else|endif|unless|endunless|loop|endloop|include|phpinclude|comment|endcomment)\s*';
			$regex.= '(?:';
			$regex.=	'(?:';
			$regex.=		'(name|format|escape|op|value|file)';
			$regex.=		'\s*=\s*';
			$regex.=	')?';
			$regex.=	'(?:[\"\'])?';
			$regex.=	'((?<=[\"\'])';
			$regex.=	'[^\"\']*|[a-z0-9_\.]*)';
			$regex.=	'[\"\']?';
			$regex.= ')?\s*';
			$regex.= '(?:';
			$regex.=	'(?:';
			$regex.=		'(name|format|escape|op|value)';
			$regex.=		'\s*=\s*';
			$regex.=	')';
			$regex.=	'(?:[\"\'])?';
			$regex.=	'((?<=[\"\'])';
			$regex.=	'[^\"\']*|[a-z0-9_\.]*)';
			$regex.=	'[\"\']?';
			$regex.= ')?\s*';
			$regex.= '(?:';
			$regex.=	'(?:';
			$regex.=		'(name|format|escape|op|value)';
			$regex.=		'\s*=\s*';
			$regex.=	')';
			$regex.=	'(?:[\"\'])?';
			$regex.=	'((?<=[\"\'])';
			$regex.=	'[^\"\']*|[a-z0-9_\.]*)';
			$regex.=	'[\"\']?';
			$regex.= ')?\s*';
			$regex.= '(?:>|\/>|}|-->){1}';
			$regex.= '/i';
			$data = preg_replace_callback($regex, 'self::_ParseTag', $data);

			if ($this->_cache)
			{
				// add cache if need be
				$this->_createCache($data);
			}
		}

		// now we must parse the $data and check for any <tmpl_include>'s
		if ($this->_debug)
			$this->DoDebugWarnings(file($tmplFile), $tmplFile);

		if ($doEval)
		{
			$success = @eval('?>'.$data.'<?php return 1;');
			$this->_includeDepth--;
			array_pop($this->_currentIncludeDir);
			return $success;
		}
		else
		{
			return $data;
		}
	}

	/**
	 * Searches for all possible instances of file { $file }
	 * @access private
	 * @param string $file path of file we're looking for
	 * @return mixed fullpath to file or boolean false
	 */
	function _FileSearch($file)
	{
		$fileName = basename($file);
		$filePath = dirname($file);

		if ($fileName == $file)
			$filePath = '';
		else
			$filePath = $filePath.'/';

		// then check for all additional given paths
		if (count($this->options['INCLUDE_PATHS']) > 0)
		{
			foreach ($this->options['INCLUDE_PATHS'] as $path)
			{
				if (is_file($path.$filePath.$fileName))
				{
					return realpath($path.$filePath.$fileName);
				}
			}
		}

		return false; // uh oh, file not found
	}

	/**
	 * Modifies the array $arr to add Template variables, __FIRST__, __LAST__ ..etc
	 * if $this->options['LOOP_CONTEXT_VARS'] is true.
	 * Used by $this->SetLoop().
	 * @access private
	 * @param array $arr
	 * @return array new look array
	 */
	function _ArrayBuild($arr)
	{
		if (is_array($arr) && !empty($arr))
		{
			$arr = array_values($arr); // to prevent problems w/ non sequential arrays
			for ($i = 0; $i < count($arr); $i++)
			{
				if (!is_array($arr[$i]))
					return false;
				foreach ($arr[$i] as $k => $v)
				{
					unset($arr[$i][$k]);
					if ($this->options['CASELESS'])
						$k = strtolower($k);
					if (preg_match('/^[0-9]+$/', $k))
						$k = '_'.$k;

					if (is_array($v))
					{
						if (($arr[$i][$k] = $this->_ArrayBuild($v)) === false) return false;
					}
					else
					{
						// reinsert the var
						$arr[$i][$k] = $v;
					}
				}
				if ($this->options['LOOP_CONTEXT_VARS'])
				{
					$_first = ($this->options['CASELESS'])? '__first__' : '__FIRST__';
					$_last = ($this->options['CASELESS'])? '__last__' : '__LAST__';
					$_inner = ($this->options['CASELESS'])? '__inner__' : '__INNER__';
					$_even = ($this->options['CASELESS'])? '__even__' : '__EVEN__';
					$_odd = ($this->options['CASELESS'])? '__odd__' : '__ODD__';
					$_rownum = ($this->options['CASELESS'])? '__rownum__' : '__ROWNUM__';

					if ($i == 0)
						$arr[$i][$_first] = true;
					if (($i + 1) == count($arr))
						$arr[$i][$_last] = true;
					if ($i != 0 && (($i + 1) < count($arr)))
						$arr[$i][$_inner] = true;
					if (is_int(($i + 1)/2))
						$arr[$i][$_even] = true;
					if (!is_int(($i + 1)/2))
						$arr[$i][$_odd] = true;
					$arr[$i][$_rownum] = ($i + 1);
				}
			}
			return $arr;
		}
		elseif (empty($arr))
		{
			return array();
		}
	}

	/**
	 * Returns a string used for parsing in tmpl_if statements.
	 * @access private
	 * @param string $varName
	 * @param string $value
	 * @param string $op
	 * @param string $nameSpace current namespace
	 * @return string used for eval'ing
	 */
	function _ParseIf($varName, $value = null, $op = null, $nameSpace = null)
	{
		if (isset($nameSpace))
			$nameSpace = substr($nameSpace, 0, -1);
		$compStr = ''; // used for extended if statements

		// work out what to put on the end id value="whatever" is used
		if (isset($value))
		{
			// add the correct operator depending on whether it's been specified or not
			if (!empty($op))
			{
				if (in_array($op, $this->allowedIfOps))
				{
					$compStr .= $op;
				}
				else
				{
					VLibTemplateError::RaiseError('VT_WARNING_INVALID_IF_OP', WARNING, $op);
				}
			}
			else
			{
				$compStr .= '==';
			}

			// now we add the value, if it's numeric, then we leave the quotes off
			if (is_numeric($value))
			{
				$compStr .= $value;
			}
			else
			{
				$compStr .= '\''.$value.'\'';
			}
		}

		if (count($this->_nameSpace) == 0 || $nameSpace == 'global')
			return '$this->_vars[\''.$varName.'\']'.$compStr;
		$retStr = '$this->_arrVars';
		$numNameSpaces = count($this->_nameSpace);
		for ($i = 0; $i < $numNameSpaces; $i++)
		{
			if ($this->_nameSpace[$i] == $nameSpace || (($i + 1) == $numNameSpaces && !empty($nameSpace)))
			{
				$retStr .= "['".$nameSpace."'][\$_".$i."]";
				break 1;
			}
			else
			{
				$retStr .= "['".$this->_nameSpace[$i]."'][\$_".$i."]";
			}
		}

		if ($this->options['GLOBAL_VARS'] && empty($nameSpace))
		{
			return '(('.$retStr.'[\''.$varName.'\'] !== null) ? '.$retStr.'[\''.$varName.'\'] : $this->_vars[\''.$varName.'\'])'.$compStr;
		}
		else
		{
			return $retStr."['".$varName."']".$compStr;
		}
	}

	/**
	 * Returns a string used for parsing in tmpl_loop statements.
	 * @access private
	 * @param string $varName
	 * @return string used for eval'ing
	 */
	function _ParseLoop($varName)
	{
		array_push($this->_nameSpace, $varName);
		$tempVar = count($this->_nameSpace) - 1;
		$retStr = '$row_count_'.$tempVar.'=count($this->_arrVars';
		for ($i = 0; $i < count($this->_nameSpace); $i++)
		{
			$retStr .= "['".$this->_nameSpace[$i]."']";
			if ($this->_nameSpace[$i] != $varName)
				$retStr .= "[\$_".$i."]";
		}
		$retStr.= '); for ($_'.$tempVar.'=0 ; $_'.$tempVar.'<$row_count_'.$tempVar.'; $_'.$tempVar.'++) {';
		return $retStr;
	}

	/**
	 * Returns a string used for parsing in tmpl_var statements.
	 * @access private
	 * @param string $wholeTag
	 * @param string $tag
	 * @param string $varName
	 * @param string $escape
	 * @param string $format
	 * @param string $nameSpace
	 * @return string used for eval'ing
	 */
	function _ParseVar($wholeTag, $tag, $varName, $escape, $format, $nameSpace)
	{
		if (!empty($nameSpace))
			$nameSpace = substr($nameSpace, 0, -1);
		$wholeTag = stripslashes($wholeTag);

		if (count($this->_nameSpace) == 0 || $nameSpace == 'global')
		{
			$var1 = '$this->_vars[\''.$varName.'\']';
		}
		else
		{
			$var1Build = "\$this->_arrVars";
			$numNameSpaces = count($this->_nameSpace);
			for ($i = 0; $i < $numNameSpaces; $i++)
			{
				if ($this->_nameSpace[$i] == $nameSpace || (($i + 1) == $numNameSpaces && !empty($nameSpace)))
				{
					$var1Build .= "['".$nameSpace."'][\$_".$i."]";
					break 1;
				}
				else
				{
					$var1Build .= "['".$this->_nameSpace[$i]."'][\$_".$i."]";
				}
			}
			$var1 = $var1Build . '[\''.$varName.'\']';

			if ($this->options['GLOBAL_VARS'] && empty($nameSpace))
			{
				$var2 = '$this->_vars[\''.$varName.'\']';
			}
		}

		$beforeVar = '';
		$afterVar  = '';
		if (!empty($escape) && isset($this->escapeTags[$escape]))
		{
			$beforeVar .= $this->escapeTags[$escape]['open'];
			$afterVar = $this->escapeTags[$escape]['close'].$afterVar;
		}

		if (!empty($format))
		{
			if (isset($this->formatTags[$format]))
			{
				$beforeVar .= $this->formatTags[$format]['open'];
				$afterVar   = $this->formatTags[$format]['close'].$afterVar;
			}
			elseif (function_exists($format))
			{
				$beforeVar .= $format.'(';
				$afterVar = ')'.$afterVar;
			}
		}

		// build return values
		$retStr  = 'if ('.$var1.' !== null) { ';
		$retStr .= 'print('.$beforeVar.$var1.$afterVar.'); ';
		$retStr .= '}';

		if (@$var2)
		{
			$retStr .= ' elseif ('.$var2.' !== null) { ';
			$retStr .= 'print('.$beforeVar.$var2.$afterVar.'); ';
			$retStr .= '}';
		}

		switch (strtolower($this->options['UNKNOWNS']))
		{
			case 'comment':
				$comment = addcslashes('<!-- unknown variable '.ereg_replace('<!--|-->', '', $wholeTag).'//-->', '"');
				$retStr .= ' else { print("'.$comment.'"); $this->_SetUnknown("'.$varName.'"); }';
				return $retStr;
				break;
			case 'leave':
				$retStr .= ' else { print("'.addcslashes($wholeTag, '"').'"); $this->_SetUnknown("'.$varName.'"); }';
				return $retStr;
				break;
			case 'print':
				$retStr .= ' else { print("'.htmlspecialchars($wholeTag, ENT_QUOTES).'"); $this->_SetUnknown("'.$varName.'"); }';
				return $retStr;
				break;
			case 'ignore':
				return $retStr;
				break;
			case 'remove':
			default:
				$retStr .= ' else { $this->_SetUnknown("'.$varName.'"); }';
				return $retStr;
				break;
		}
	}

	/**
	 * Parses a string in an include tag, i.e.:
	 * <TMPL_INCLUDE FILE="footer_{var:footer_number}.html" />
	 * @access private
	 * @param string file name
	 */
	function _ParseIncludeFile($file)
	{
		$regex = '/\{var:([^\}]+)\}/i';
		$file = preg_replace($regex, "'.\$this->_vars['\\1'].'", $file);
		return $file;
	}


	/**
	 * Takes values from preg_replace in $this->_IntParse() and determines
	 * the replace string.
	 * @access private
	 * @param array $args array of all matches found by preg_replace
	 * @return string replace values
	 */
	function _ParseTag($args)
	{
		$wholeTag = $args[0];
		$openclose = $args[1];
		$tag = strtolower($args[2]);

		if ($tag == 'else')
			return '<?php } else { ?>';

		if (preg_match("/^<\/|{\/|<!--\/$/s", $openclose) || preg_match("/^end[if|loop|unless|comment]$/", $tag))
		{
			if ($tag == 'loop' || $tag == 'endloop') array_pop($this->_nameSpace);
			if ($tag == 'comment' || $tag == 'endcomment')
			{
				return '<?php */ ?>';
			}
			else
			{
				return '<?php } ?>';
			}
		}

		// arrange attributes
		for ($i = 3; $i < 8; $i = ($i + 2))
		{
			if (empty($args[$i]) && empty($args[($i+1)]))
				break;
			$key = (empty($args[$i])) ? 'name' : strtolower($args[$i]);
			if ($key == 'name' && preg_match('/^(php)?include$/', $tag))
				$key = 'file';
			$$key = $args[($i + 1)];
		}

		if (isset($name))
		{
			$var = ($this->options['CASELESS']) ? strtolower($name) : $name;

			if ($this->_debug && !empty($var))
			{
				if (preg_match("/^global\.([A-Za-z_]+[_A-Za-z0-9]*)$/", $var, $matches))
					$var2 = $matches[1];
				if (empty($this->_debugTemplateVars[$tag]))
					$this->_debugTemplateVars[$tag] = array();
				if (!isset($var2))
					$var2 = $var;
				if (!in_array($var2, $this->_debugTemplateVars[$tag]))
					array_push($this->_debugTemplateVars[$tag], $var2);
			}

			if (preg_match("/^([A-Za-z_]+[_A-Za-z0-9]*(\.)+)?([A-Za-z_]+[_A-Za-z0-9]*)$/", $var, $matches))
			{
				$var = $matches[3];
				$nameSpace = $matches[1];
			}
		}

		// return correct string (tag dependent)
		switch ($tag)
		{
			case 'var':
				if (empty($escape) && (!empty($this->options['DEFAULT_ESCAPE']) && strtolower($this->options['DEFAULT_ESCAPE']) != 'none'))
				{
					$escape = strtolower($this->options['DEFAULT_ESCAPE']);
				}
				return '<?php '.$this->_ParseVar ($wholeTag, $tag, $var, @$escape, @$format, @$nameSpace).' ?>';
				break;
			case 'if':
				return '<?php if ('. $this->_ParseIf($var, @$value, @$op, @$nameSpace) .') { ?>';
				break;
			case 'unless':
				return '<?php if (!'. $this->_ParseIf($var, @$value, @$op, @$nameSpace) .') { ?>';
				break;
			case 'elseif':
				return '<?php } elseif ('. $this->_ParseIf($var, @$value, @$op, @$nameSpace) .') { ?>';
				break;
			case 'loop':
				return '<?php '. $this->_ParseLoop($var) .'?>';
				break;
			case 'comment':
				if (empty($var))
				{
					// full open/close style comment
					return '<?php /* ?>';
				}
				else
				{
					// just ignore tag if it was a one line comment
					return;
				}
				break;
			case 'phpinclude':
				if ($this->options['ENABLE_PHPINCLUDE'])
				{
					return '<?php include(\''.$file.'\'); ?>';
				}
				break;
			case 'include':
				return '<?php $this->_GetData($this->_FileSearch(\''.$this->_ParseIncludeFile($file).'\'), 1); ?>';
				break;
			default:
				if ($this->options['STRICT'])
					VLibTemplateError::RaiseError('VT_ERROR_INVALID_TAG', KILL, htmlspecialchars($wholeTag, ENT_QUOTES));
				break;
		}
	}

	/**
	 * Parses $this->_tmplFile into correct format for eval() to work
	 * Called by $this->_Parse(), or $this->FastPrint, this replaces all <tmpl_*> references
	 * with their correct php representation, i.e. <tmpl_var title> becomes $this->vars['title']
	 * Sets final parsed file to $this->_tmplFileP.
	 * @access private
	 * @return boolean true/false
	 */
	function _IntParse()
	{
		$mqrt = get_magic_quotes_runtime();
		//set_magic_quotes_runtime(0);
		$this->_tmplFileP = '?>'.$this->_GetData($this->_tmplFileName).'<?php return true;';
		//set_magic_quotes_runtime($mqrt);
		return true;
	}

	/**
	 * Calls _IntParse, and eval()s $this->tmplfilep
	 * and outputs the results to $this->tmploutput
	 * @access private
	 * @param bool compress whether to compress contents
	 * @return boolean true/false
	 */
	function _Parse($compress = null)
	{
		if (!$this->_parsed)
		{
			if ($this->options['TIME_PARSE'])
				$this->_firstParseTime = $this->_GetMicrotime();

			$this->_IntParse();
			$this->_parsed = true;

			if ($this->options['TIME_PARSE'])
				$this->_totalParseTime = ($this->_GetMicrotime() - $this->_firstParseTime);
			if ($this->options['TIME_PARSE'] && $this->options['GLOBAL_CONTEXT_VARS'])
				$this->SetVar('__PARSE_TIME__', $this->GetParseTime());
		}

		ob_start($compress);

		array_push($this->_currentIncludeDir, dirname($this->_tmplFileName));
		$this->_includeDepth++;
		$success = @eval($this->_tmplFileP);
		$this->_includeDepth--;
		array_pop($this->_currentIncludeDir);
		if ($this->_debug)
			$this->DoDebug();
		if (!$success)
			VLibTemplateError::RaiseError('VT_ERROR_PARSE', FATAL);
		$this->_tmplOutput .= ob_get_contents();

		ob_end_clean();

		return true;
	}

	/**
	 * Sets one or more of the boolean options 1/0, that control certain actions in the template.
	 * Use of this function:
	 * either: VLibTemplate::_SetOptions(string option_name, bool option_val [, string option_name, bool option_val ..]);
	 * or	  VLibTemplate::_SetOptions(array);
	 *		  with an associative array where the key is the option_name
	 *		  and the value is the option_value.
	 *
	 * @access private
	 * @param mixed (mulitple)
	 * @return bool true/false
	 */
	function _SetOption()
	{
		$numargs = func_num_args();
		if ($numargs < 1)
		{
			VLibTemplateError::RaiseError('VT_ERROR_WRONG_NO_PARAMS', null, '_SetOption()');
			return false;
		}

		if ($numargs == 1)
		{
			$options = func_get_arg(1);
			if (is_array($options))
			{
				foreach ($options as $k => $v)
				{
					if ($v != null)
					{
						if (in_array($k, array_keys($this->options)))
							$this->options[$k] = $v;
					}
					else
					{
						continue;
					}
				}
			}
			else
			{
				VLibTemplateError::RaiseError('VT_ERROR_WRONG_NO_PARAMS', null, '_SetOption()');
				return false;
			}
		}
		elseif (is_int($numargs/2))
		{
			for ($i = 0; $i < $numargs; $i = ($i + 2))
			{
				$k = func_get_arg($i);
				$v = func_get_arg(($i + 1));
				if ($v != null)
				{
					if (in_array($k, array_keys($this->options)))
						$this->options[$k] = $v;
				}
			}
		}
		else
		{
			VLibTemplateError::RaiseError('VT_ERROR_WRONG_NO_PARAMS', null, '_SetOption()');
			return false;
		}
		return true;
	}

	/**
	 * Used during parsing, this function sets an unknown var checking to see if it
	 * has been previously set.
	 * @access private
	 * @param string var
	 */
	function _SetUnknown($var)
	{
		if (!in_array($var, $this->_unknowns))
			array_push($this->_unknowns, $var);
	}

	/**
	 * Returns microtime as a float number
	 * @access private
	 * @return float microtime
	 */
	function _GetMicrotime()
	{
		list($msec, $sec) = explode(" ",microtime());
		return ((float)$msec + (float)$sec);
	}

	/**
	 * Returns str encoded to hex code.
	 * @access private
	 * @param string str to be encoded
	 * @param bool true/false specify whether to use hex_entity
	 * @return string encoded in hex
	 */
	function _EscapeHex($str = "", $entity = false)
	{
		$prestr = $entity ? '&#x' : '%';
		$poststr= $entity ? ';' : '';
		for ($i = 0; $i < strlen($str); $i++)
		{
			$return .= $prestr.bin2hex($str[$i]).$poststr;
		}
		return $return;
	}

	function _EscapeJS($str = "")
	{
		return htmlspecialchars(addcslashes($str, "\r\n'\\"));
	}

	// The following functions have no use and are included just so that if the user
	// is making use of VLibTemplateCache functions, this doesn't crash when changed to
	// VLibTemplate if the user is quickly bypassing the VLibTemplateCache class.
	function ClearCache() { VLibTemplateError::RaiseError('VT_WARNING_NOT_CACHE_OBJ', WARNING, 'ClearCache()'); }
	function ReCache() { VLibTemplateError::RaiseError('VT_WARNING_NOT_CACHE_OBJ', WARNING, 'ReCache()'); }
	function SetCacheLifeTime() { VLibTemplateError::RaiseError('VT_WARNING_NOT_CACHE_OBJ', WARNING, 'SetCacheLifeTime()'); }
	function SetCacheExtension() { VLibTemplateError::RaiseError('VT_WARNING_NOT_CACHE_OBJ', WARNING, 'SetCacheExtension()'); }
}

//include_once(dirname(__FILE__).'/debug.php');
include_once(dirname(__FILE__).'/cache.php');

?>