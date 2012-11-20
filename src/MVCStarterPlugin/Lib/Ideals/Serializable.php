<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Ideals;

interface Serializable
{
	/**
	 * Returns a record with the given id
	 * 
	 * This should return the same results as findBy('id', $id);
	 * 
	 * @param $id int the id of the record to find
	 * @return Serializable an instance of the concrete class with the given ID 
	 */
	static public function find($id);

	/**
	 * Returns all records of this class
	 * 
	 * Returns all records of this class wrapped in an array. If you call this
	 * method, make sure that you do not have a very large database, as the cost
	 * of instantiating more than a few objects would become quite large.
	 * A beter method would be to return a "Collection" object that implements
	 * Iterable and only instantiates several records at a time, possibly allowing for
	 * paging of results.
	 * 
	 * @return Array an array of all objects of this type from the database.
	 */
	static public function findAll();

	/**
	 * Returns all records by column value
	 * 
	 * Finds all records where the specified column's value is equal to
	 * the value passed in. Returns same results as findWhere($col, '=', $value)
	 * 
	 * @param $col string the name of the column/field to find
	 * @param $value string the value to mach the column on
	 */
	static public function findBy($col, $value);

	/**
	 * Returns all records matching specifications
	 * 
	 * Finds all records where the column is related to the value via the relation
	 * given.
	 * 
	 * @param $col string the column to find
	 * @param $relation string the relation operator to use, e.g. '=', 'LIKE', '>'
	 * @param $value string the value of the field to match the column on
	 */
	static public function findWhere($col, $relation, $value);

	/**
	 * Removes a record from the database
	 * 
	 * Removes the record with the given id from the database
	 * 
	 * @param $id int the id of the record to destroy
	 * @param $cascade boolean if true, attempts to delete all child objects as well
	 * @return boolean true on success, false on failure
	 */
	static public function destroy($id, $cascade);

	/**
	 * Saves the record
	 * 
	 * Saves the current object in the database. If no record exists yet, it creates a
	 * new record; otherwise, it updates the existing record.
	 * 
	 * @param $cascade boolean if true, a "deep save" is performed, saving all child
	 * elements as well.
	 */
	public function save($cascade);

	/**
	 * Gets this object's children
	 * 
	 * Finds all elements of a given model that belong to this instance. This assumes that
	 * all relationships are kept in the child element. That is, in a one-to-one
	 * relationship, it is assumed that the `child` table has a `parent_id` column, not
	 * vice versa.
	 * 
	 * @param $model the model/table name of the child object type
	 * @return array a collection of all children records
	 */
	static public function getChildren($model);

	/**
	 * Gets an array of the elements that should be safed to the database
	 * 
	 * @return array the column names of the attributes that exist in the database.
	 */
	static public function getPersistentAttrubutes();

// UTILITY METHODS

	/**
	 * Indicates whether this is a new (unsaved) record
	 * 
	 * @return boolean true if this is a new (unsaved) record, false otherwise.
	 */
	public function isNewRecord();
}