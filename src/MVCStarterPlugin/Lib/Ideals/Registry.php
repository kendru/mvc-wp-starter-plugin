<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Ideals;

/**
 * A registry for storing data in key-value pairs
 * The registry allows clients to get and set data that
 * may be accessed via some global context (e.g. session or request).
 * Using the registry is a means of controlling access to global
 * variables. Implementing classes are encouraged to define discreet
 * accesors for only the properties that will be used in the application
 * rather than relying on the generic <code>get()</code> and <code>set()</code>
 * methods for data access.
 * We recommend making the implementing classes Singletons, but this is not
 * strictly enforced by the interface.
 */
interface Registry
{
	/** @type int flag for overwrite on conflict mode */
	const CONFLICT_OVERWRITE = 20;
	/** @type int flag for throw exception on conflict mode */
	const CONFLICT_EXCEPTION = 21;
	/** @type int flag for do nothing on conflict mode */
	const CONFLICT_SILENT = 22;

	/**
	 * Gets a value stored in the registry.
	 * Returns a value stored in the Registry with the given key.
	 * 
	 * @param string $key the key of the value to retrieve.
	 * @return mixed|null the regstered value requested, null if key does not exist
	 */
	public function get($key);

	/**
	 * Sets a value in the registry.
	 * Sets a value in the registry, associating it with a given key. When
	 * a value is already associated with this registry key, the conflict
	 * determines how this method responds
	 * 
	 * @param string $key the registry key to store the value in
	 * @param mixed $value the value to store in the registry
	 * 
	 * @return mixed|null the value saved upon success, null upon
	 * failure (if the conflict mode allows the method to return)
	 */
	public function set($key, $value);

	/**
	 * Sets the conflict mode for the registry
	 * The conflict mode determines how to handle the case where a key that
	 * has already been set is reset. Depending on the client, this may
	 * need to overwrite the key, throw an exception, or fail silently (not
	 * recommended).
	 * 
	 * @param int $mode the flag to determine what conflict mode to use.
	 * @return void
	 */
	public function setConflictMode($mode);
}