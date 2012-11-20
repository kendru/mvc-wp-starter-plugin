<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Util;

use MVCStarterPlugin\Lib\Exceptions\InvalidSQLException;

/**
 * Sanitize SQL queries
 * 
 * Sanitizes elements of an SQL statement to prevent SQL injection attacks
 */
class SQLSanitizer
{
	const OP_ARITHMETIC = 1;
	const OP_COMPARISON = 2;
	const OP_LOGICAL	= 4;
	const OP_ANY		= 7;

	const TYPE_NUMERIC	= 5;
	const TYPE_TEXTUAL	= 10;
	const TYPE_DATE		= 15;

	/**
	 * Sanitizes keyword
	 * Ensures that given argument is a valid SQL keyword
	 * 
	 * @param string $keyword the keyword to check
	 * @return string the $keyword passed in
	 * @throws \MVCStarterPlugin\Lib\Exceptions\InvalidSQLException
	 */
	public static function sanitizeKeyword($keyword)
	{
		$keyword = strtoupper($keyword);
		$valid_keywords = array(
		'ABS','ALL','ALLOCATE','ALTER','AND','ANY','ARE','ARRAY','AS','ASENSITIVE','ASYMMETRIC','AT','ATOMIC','AUTHORIZATION','AVG',
		'BEGIN','BETWEEN','BIGINT','BINARY','BLOB','BOOLEAN','BOTH','BY',
		'CALL','CALLED','CARDINALITY','CASCADED','CASE','CAST','CEIL','CEILING','CHAR','CHAR_LENGTH','CHARACTER','CHARACTER_LENGTH','CHECK','CLOB','CLOSE','COALESCE','COLLATE','COLLECT','COLUMN','COMMIT','COMPARABLE','CONDITION','CONNECT','CONSTRAINT','CONVERT','CORR','CORRESPONDING','COUNT','COVAR_POP','COVAR_SAMP','CREATE','CROSS','CUBE','CUME_DIST','CURRENT','CURRENT_CATALOG','CURRENT_DATE','CURRENT_DEFAULT_TRANSFORM_GROUP','CURRENT_PATH','CURRENT_ROLE','CURRENT_SCHEMA','CURRENT_TIME','CURRENT_TIMESTAMP','CURRENT_TRANSFORM_GROUP_FOR_TYPE','CURRENT_USER','CURSOR','CYCLE',
		'DATE','DAY','DEALLOCATE','DEC','DECIMAL','DECLARE','DEFAULT','DELETE','DENSE_RANK','DEREF','DESCRIBE','DETERMINISTIC','DISCONNECT','DISTINCT','DO','DOUBLE','DROP','DYNAMIC',
		'EACH','ELEMENT','ELSE','ELSEIF','END','END_EXEC','ESCAPE','EVERY','EXCEPT','EXEC','EXECUTE','EXISTS','EXIT','EXP','EXTERNAL','EXTRACT',
		'FALSE','FETCH','FILTER','FIRST_VALUE','FLOAT','FLOOR','FOR','FOREIGN','FREE','FROM','FULL','FUNCTION','FUSION',
		'GET','GLOBAL','GRANT','GROUP','GROUPING',
		'HANDLER','HAVING','HOLD','HOUR',
		'IDENTITY','IN','INDICATOR','INNER','INOUT','INSENSITIVE','INSERT','INT','INTEGER','INTERSECT','INTERSECTION','INTERVAL','INTO','IS','ITERATE',
		'JOIN',
		'LAG','LANGUAGE','LARGE','LAST_VALUE','LATERAL','LEAD','LEADING','LEAVE','LEFT','LIKE','LIKE_REGEX','LN','LOCAL','LOCALTIME','LOCALTIMESTAMP','LOOP','LOWER',
		'MATCH','MAX','MAX_CARDINALITY','MEMBER','MERGE','METHOD','MIN','MINUTE','MOD','MODIFIES','MODULE','MONTH','MULTISET',
		'NATIONAL','NATURAL','NCHAR','NCLOB','NEW','NO','NONE','NORMALIZE','NOT','NTH_VALUE','NTILE','NULL','NULLIF','NUMERIC',
		'OCCURRENCES_REGEX','OCTET_LENGTH','OF','OFFSET','OLD','ON','ONLY','OPEN','OR','ORDER','OUT','OUTER','OVER','OVERLAPS','OVERLAY',
		'PARAMETER','PARTITION','PERCENT_RANK','PERCENTILE_CONT','PERCENTILE_DISC','POSITION','POSITION_REGEX','POWER','PRECISION','PREPARE','PRIMARY','PROCEDURE',
		'RANGE','RANK','READS','REAL','RECURSIVE','REF','REFERENCES','REFERENCING','REGR_AVGX','REGR_AVGY','REGR_COUNT','REGR_INTERCEPT','REGR_R2','REGR_SLOPE','REGR_SXX','REGR_SXY','REGR_SYY','RELEASE','REPEAT','RESIGNAL','RESULT','RETURN','RETURNS','REVOKE','RIGHT','ROLLBACK','ROLLUP','ROW','ROW_NUMBER','ROWS',
		'SAVEPOINT','SCOPE','SCROLL','SEARCH','SECOND','SELECT','SENSITIVE','SESSION_USER','SET','SIGNAL','SIMILAR','SMALLINT','SOME','SPECIFIC','SPECIFICTYPE','SQL','SQLEXCEPTION','SQLSTATE','SQLWARNING','SQRT','STACKED','START','STATIC','STDDEV_POP','STDDEV_SAMP','SUBMULTISET','SUBSTRING','SUBSTRING_REGEX','SUM','SYMMETRIC','SYSTEM','SYSTEM_USER',
		'TABLE','TABLESAMPLE','THEN','TIME','TIMESTAMP','TIMEZONE_HOUR','TIMEZONE_MINUTE','TO','TRAILING','TRANSLATE','TRANSLATE_REGEX','TRANSLATION','TREAT','TRIGGER','TRIM','TRIM_ARRAY','TRUE','TRUNCATE',
		'UESCAPE','UNDO','UNION','UNIQUE','UNKNOWN','UNNEST','UNTIL','UPDATE','UPPER','USER','USING',
		'VALUE','VALUES','VAR_POP','VAR_SAMP','VARBINARY','VARCHAR','VARYING',
		'WHEN','WHENEVER','WHERE','WIDTH_BUCKET','WINDOW','WITH','WITHIN','WITHOUT','WHILE',
		'YEAR');

		if (in_array($keyword, $valid_keywords)) {
			return $keyword;
		} else {
			throw new InvalidSQLException("Not a valid SQL keyword: '$keyword'");
		}
	}

	/**
	 * Sanitizes operator
	 * Ensures that given argument is a valid SQL operator
	 * 
	 * @param string $operator the operator to check
	 * @param int $type optional intended type of the operator
	 * @return string the $operator passed in
	 * @throws \MVCStarterPlugin\Lib\Exceptions\InvalidSQLException
	 */
	public static function sanitizeOperator($operator, $type = self::OP_ANY)
	{
		$operator = strtoupper($operator);
		$operators_to_test = array();
		$arithmetic_operators = array(
			'+','-','*','/','%'
		);
		$comparison_operators = array(
			'=','!=','<>','>','<','>=','<=','!<','!>'
		);
		$logical_operators = array(
			'ALL','AND','ANY','BETWEEN','EXISTS','IN','LIKE','OR','IS NULL','UNIQUE',
			'NOT BETWEEN','NOT EXISTS','NOT IN','NOT LIKE','IS NOT NULL',
		);

		if ($type & self::OP_ARITHMETIC) {
			$operators_to_test += $arithmetic_operators;
		}
		if ($type & self::OP_COMPARISON) {
			$operators_to_test += $comparison_operators;
		}
		if ($type & self::OP_LOGICAL) {
			$operators_to_test += $logical_operators;
		}

		if (in_array($operator, $operators_to_test)) {
			return $operator;
		} else {
			throw new InvalidSQLException("Not a valid SQL operator: '$operator'");
		}
	}

	/**
	 * Sanitizes SQL parameter
	 * 
	 * @param mixed $param the parameter to sanitize
	 * @param int $type optional type of parameter
	 * @param boolean $escape TODO optional flag indicating whether to escape special characters before returning
	 * @return mixed the sanitized parameter
	 */
	public static function sanitizeParameter($param, $type = self::TYPE_TEXTUAL, $escape = false)
	{
		switch ($type) {
			case self::TYPE_TEXTUAL:
				$param = preg_replace('/^[\'|\"](.*)[\'|\"]$', '$1', $param);
				return preg_replace("([\x00\n\r\\'\"])", '\$1', $param);
				break;
			case self::TYPE_NUMERIC:
				return preg_replace('/[^\d.]/', '', $param);
				break;
			case self::TYPE_DATE:
				return preg_replace('/[^\d.\-\s\/]/', '', $param);
				break;
			default:
				throw new \InvalidArgumentException("Invalid data type given");
				break;
		}
	}
}