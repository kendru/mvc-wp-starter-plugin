<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Common;

use MVCStarterPlugin\Lib\Util\Inflector;

/**
 * Provides unified access to a collection of Model objects
 * 
 * Contains a number of Model objects that may be accessed as an array, may be counted
 * and may be iterated. This class handles lazy database access and object instantiation
 * to reduce overhead as much as possible.
 * 
 * Currently, this class only supports simple sequential iteration of database records,
 * but there are plans to add functionality to the CollectionFilter class that would
 * allow fluent filtering of record sets.
 */
class Collection implements \ArrayAccess, \Countable, \Iterator
{
	/** @type CollectionBuilder $builder the builder object to use */
	protected $filter;

	/** @type string $model the fully-qualified model class to instantiate */
	protected $model;

	/** @type int $preload the number of records to load at one time */
	protected $preload;

	/** @type int $cursor the position of the current record */
	private $cursor = 0;

	/** @type int $current_page the current "page" of results */
	private $current_page = 1;

	/** @type bool $has_more_records flag of whether more records exist for collection */ 
	private $has_more_records;

	public function __construct($model, $preload = 20)
	{
		$this->model		= $model;
		$this->preload 		= $preload;
		$this->filter 		= new CollectionBuilder(Inflector::tableize(Inflector::denamespace($model))); 
		$this->filter->limit(0, $preload);
	}

//================================
//  Interface Compliance Methods
//================================

// ArrayAccess
	public function offsetExists($offset)
	{
		# code...
	}

	public function offsetGet($offset)
	{	
		# code...
	}

	public function offsetSet($offset, $value) // Note: $offset may be NULL
	{
		# code...
	}

	public function offsetUnset($offset)
	{
		# code...
	}

// Countable
	public function count()
	{
		# code...
	}

//Iterator
	public function current()
	{
		# code...
	}

	public function key()
	{
		# code...
	}

	public function next()
	{
		// Advance the cursor
		if ($this->has_more_records) {
			$this->cursor += 1;
		}

		if ($this->preload % $this->cursor) {
			# code...
		}
	}

	public function rewind()
	{
		# code...
	}

	public function valid()
	{
		# code...
	}
}