<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Ideals;

// TODO: We actually need to have several interfaces: FluentBuilder, FluentClause, FluentPredicate, FluentExpression, and FluentStatement
// Maybe we should just scrap this and make Collection a pseodo-fluent builder

/**
 * DSL for building SQL queries
 */
interface FluentBuilder
{
	const JOIN_LEFT = 5;
	const JOIN_INNER = 10;

	public function select($cols);
	public function from($table);
	public function join($col, $on_left, $on_right, $join_type);
	public function where($col, $op, $value);
	public function logicalAnd(FluentBuilder $l, FluentBuilder $r);
	public function logicalOr(FluentBuilder $l, FluentBuilder $r);
	public function logicalNot();
	public function orderBy($col);
	public function groupBy($col, $dir);
	public function limit($num, $start_at);
}