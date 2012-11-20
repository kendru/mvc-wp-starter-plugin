<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Common;

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
class CollectionBuilder
{
	/** @type string $table the name of the table to retrieve results from */
	protected $table;

	/** @type string $where the where clause of the query */
	protected $where;

	/** @type int $start the index of the first record to retrieve */
	private $start;

	/** @type int $qty the the number of records to retrieve */
	private $qty;

	public function __construct($table)
	{
		$this->table = $table;
	}

	public function where($where)
	{
		$this->where = $where;
	}

	public function limit($start, $qty)
	{
		$this->start 	= $start;
		$this->qty 		= $qty;
	}

	// Return a query string to execute
	public function __toString()
	{
		$select_clause 	= 'SELECT * FROM ' . $table_name;
		$where_clause 	= ($this->where) ? ' WHERE ' . $this->where : '';
		return $select_clause . $where_clause;
	}
}