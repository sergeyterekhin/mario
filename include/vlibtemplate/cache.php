<?php

/**
 * Class uses all of VLibTemplate's functionality but caches the template files.
 * It creates an identical tree structure to your filesystem but with cached files.
 *
 * @package ESLibrary
 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
 * @copyright 2002 Active Fish Group
 * @since 2006-01-11
 */
class VLibTemplateCache extends VLibTemplate
{
	/** Tells VLibTemplate that we're caching */
	var $_cache = 1;
	/** full path to current cache file (even if it doesn't yet exist) */
	var $_cacheFile;
	/** has this file been cached before */
	var $_cacheExists;
	/** is this file currently locked whilst writing */
	var $_cacheFileLocked;
	/** dir of current cache file */
	var $_cacheFileDir; // dir of current cache file
	/**  */
	var $_clearCache = 0;

	/**
	 * Will unset a file, and set $this->_cacheExists to 0.
	 * @access public
	 * @return boolean
	 */
	function ClearCache()
	{
		$this->_clearCache = 1;
		return true;
	}

	/**
	 * alias for ClearCache().
	 * @access public
	 * @return boolean
	 */
	function ReCache()
	{
		return $this->ClearCache();
	}

	/**
	 * Sets the lifetime of the cached file
	 * @access public
	 * @param int $int number of seconds to set lifetime to
	 * @return boolean
	 */
	function SetCacheLifeTime($int = null)
	{
		if ($int == null || !is_int($int))
			return false;
		if ($int == 0)
			$int = 60;
		if ($int == -1)
			$int = 157680000; // set to 5 yrs time
		$this->options['CACHE_LIFETIME'] = $int;
		return true;
	}

	/**
	 * Sets the extention of the cache file
	 * @access public
	 * @param str $str name of new cache extention
	 * @return boolean
	 */
	function SetCacheExtension($str = null)
	{
		if ($str == null || !ereg('^[a-z0-9]+$', strtolower($str)))
			return false;
		$this->options['CACHE_EXTENSION'] = strtolower($str);
		return true;
	}

	/**
	 * Checks if there's a cache, if there is then it will read the cache file as the template.
	 * @access private
	 * @param str $tmpFile name of the template file
	 * @return boolean
	 */
	function _CheckCache($tmplFile)
	{
		$this->_cacheFile = $this->_GetFilename($tmplFile);
		if ($this->_clearCache)
		{
			if (file_exists($this->_cacheFile))
				unlink($this->_cacheFile);
			return false;
		}

		if (file_exists($this->_cacheFile))
		{
			$this->_cacheExists = 1;

			// if it's expired
			if ((filemtime($this->_cacheFile) + $this->options['CACHE_LIFETIME']) < date ('U')
				|| filemtime($this->_cacheFile) < filemtime($tmplFile))
			{
				$this->_cacheExists = 0;
				return false; // so that we know to recache
			}
			else
			{
				return true;
			}

		}
		else
		{
			$this->_cacheExists = 0;
			return false;
		}
	}

	/**
	 * Gets the full pathname for the cached file
	 * @access private
	 * @param str $tmpFile name of the template file
	 * @return string full pathname for the cached file
	 */
	function _GetFilename($tmplFile)
	{
		return $this->options['CACHE_DIR'].md5('VLibCachestaR'.realpath($tmplFile)).'.'.$this->options['CACHE_EXTENSION'];
	}

	/**
	 * Creates the cached file
	 * @access private
	 * @param str $data content of the cache file
	 * @return boolean
	 */
	function _CreateCache($data)
	{
		$cacheFile = $this->_cacheFile;
		if(!$this->_PrepareDirs($cacheFile))
			return false; // prepare all of the directories

		$f = fopen ($cacheFile, "w");
		flock($f, 2); // set an exclusive lock
		if (!$f)
			VLibTemplateError::RaiseError('VT_ERROR_NO_CACHE_WRITE', KILL, $cacheFile);
		fputs($f, $data); // write the parsed string from VLibTemplate
		flock($f, 3); // unlock file
		fclose($f);
		touch($cacheFile);
		@chmod($cacheFile, 0666);
		return true;
	}

	/**
	 * Prepares the directory structure
	 * @access private
	 * @param str $file full pathname for the cached file
	 * @return boolean
	 */
	function _PrepareDirs($file)
	{
		if (empty($file))
			die('no filename'); // do error in future

		$filePath = dirname($file);
		if (is_dir($filePath))
			return true;

		$dirs = split('[\\/]', $filePath);
		$currPath = "";
		foreach ($dirs as $dir)
		{
			$currPath .= $dir .'/';
			$type = @filetype($currPath);

			($type == 'link') and $type = 'dir';
			if ($type != 'dir' && $type != false && !empty($type))
			{
				VLibTemplateError::RaiseError('VT_ERROR_WRONG_CACHE_TYPE', KILL, 'directory: '.$currPath.', type: '.$type);
			}
			if ($type == 'dir')
			{
				continue;
			}
			else
			{
				$s = @mkdir($currPath, 0777);
				if (!$s)
					VLibTemplateError::RaiseError('VT_ERROR_CACHE_MKDIR_FAILURE', KILL, 'directory: '.$currPath);
			}
		}
		return true;
	}
}

?>