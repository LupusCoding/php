<?php
namespace Vendor;

/**
 * PSR-4 compatible Autoloader
 *
 * Initialize Autoloader:
 * 		<?php
 * 		$loader = new \Vendor\Psr4Loader;
 * 		$loader->register();
 *
 * Map base directories to namespace prefix:
 *		<?php
 *		$loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src')
 *		$loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/tests')
 *
 * Examples:
 * Example file paths:
 * 		/path/to/packages/foo-bar/
 * 			src/
 * 				Baz.php					=> \Foo\Bar\Baz
 * 				Alpha/
 * 					Omega.php			=> \Foo\Bar\Alpha\Omega
 * 			tests/
 * 				BazTest.php				=> \Foo\Bar\BazTest
 * 				Alpha/
 * 					OmegaTest.php		=> \Foo\Bar\Alpha\OmegaTest
 *
 * After Mapping the directories this way, the loader will search the map
 * for files. The following example would load:
 * /path/to/packages/foo-bar/src/Alpha/Omega.php
 * 		<?php
 *		new \Foo\Bar\Alpha\Omega;
 *
 * The following example would load:
 * /path/to/packages/foo-bar/tests/Alpha/OmegaTest.php
 * 		<?php
 *		new \Foo\Bar\Alpha\OmegaTest;
 *
 */
class Psr4Loader
{
	/**
	 * Associative array for namespace mapping
	 * Key 	 = Namespace prefix
	 * Value = Array with base directories
	 *
	 * @var array
	 */
	protected $prefixes = array();

    /**
     * register autoloader with SPL autoload stack
     *
     * @return void
     */
	public function register()
	{
		spl_autoload_register( array($this, 'loadClass') );
	}

    /**
     * Add base directory to prefix map
     *
     * @param string $prefix 	Namespace prefix
     * @param string $base_dir 	Base dir for class files in namespace
     * @param bool $prepend 	If true, prepend base dir instead of append it
     * @return void
     */
	public function addNamespace($prefix, $base_dir, $prepend = false) 
	{
		// normalize namespace prefix
		$prefix = trim($prefix, '\\') . '\\';

		// normalize base_dir with a DIRECTORY_SEPARATOR
		$base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

		// initialize new subarray if prefix not exist yet
		if(isset($this->prefixes[$prefix]) === false)
		{
			$this->prefixes[$prefix] = array();
		}

		// add the base_dir to the prefix
		if($prepend === true)
		{
			array_unshift($this->prefixes[$prefix], $base_dir);
		} else {
			array_push($this->prefixes[$prefix], $base_dir);
		}
	}

    /**
     * require a file if it exist
     *
     * @param string $file	File path
     * @return bool True if file exist, false otherwise
     */
	public function reqFile($file)
	{
		if(file_exists($file))
		{
			// require_once instead of require to
			// prevent loading the same file multiple times
			require_once($file);
			return true;
		}
		return false;
	}

    /**
     * Load mapped file by namespace prefix map
     *
     * @param string $prefix	Namespace prefix
     * @param string $class		Relative class name
     * @return mixed False if no mapped file could be loaded
     * 				 Filename if a file could be loaded
     */
	public function loadMappedFile($prefix, $class)
	{
		// check if we know the namespace prefix
		if(isset($this->prefixes[$prefix]) === false)
		{
			return false;
		}

		// if we know the prefix, we try to find the file
		foreach($this->prefixes[$prefix] as $base_dir)
		{
			/* get the file path by
			 * 1. starting with base_dir
			 * 2. replace namespace seperators with DIRECTORY_SEPARATOR
			 * 3. append with '.php'
			 */
			$file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

			// if we found a file, require it
			if($this->reqFile($file))
			{
				return $file;
			}
		}

		// if we end here, no file was found
		return false;
	}

    /**
     * Load class by class name
     *
     * @param string $class Fully-qualified class name
     * @return mixed False on failure, otherwise mapped file name
     */
	public function loadClass($class)
	{
		// get prefix from class
		$prefix = $class;
		while( false !== ($pos = strrpos($prefix, '\\') )
		{
			// split class to prefix and relative classname
			$prefix = substr($class, 0, $pos, $pos + 1);
			$relative = substr($class, $pos + 1);

			// try to load a mapped file
			$mapped = $this->loadMappedFile($prefix, $relative);
			if($mapped)
			{
				return $mapped;
			}

			// remove the trailing namespace seperator for next iteration
			$prefix = rtrim($prefix, '\\');
		}

		// if we end here, no file was found
		return false;
	}

}