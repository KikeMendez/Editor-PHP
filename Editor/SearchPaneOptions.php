<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @copyright 2016 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *  @link      http://editor.datatables.net
 */

namespace DataTables\Editor;
if (!defined('DATATABLES')) exit();

use DataTables;

/**
 * The Options class provides a convenient method of specifying where Editor
 * should get the list of options for a `select`, `radio` or `checkbox` field.
 * This is normally from a table that is _left joined_ to the main table being
 * edited, and a list of the values available from the joined table is shown to
 * the end user to let them select from.
 *
 * `Options` instances are used with the {@link Field::options} method.
 *
 *  @example
 *   Get a list of options from the `sites` table
 *    <code>
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *        )
 *    </code>
 *
 *  @example
 *   Get a list of options with custom ordering
 *    <code>
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *            ->order( 'name DESC' )
 *        )
 *    </code>
 *
 *  @example
 *   Get a list of options showing the id and name in the label
 *    <code>
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( [ 'name', 'id' ] )
 *            ->render( function ( $row ) {
 *              return $row['name'].' ('.$row['id'].')';
 *            } )
 *        )
 *    </code>
 */
class SearchPaneOptions extends DataTables\Ext {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */
	
	/** @var string Table to get the information from */
	private $_table = null;

	/** @var string Column name containing the value */
	private $_value = null;

	/** @var string[] Column names for the label(s) */
	private $_label = array();

	private $_leftJoin = array();

	/** @var integer Row limit */
	private $_limit = null;

	/** @var callable Callback function to do rendering of labels */
	private $_renderer = null;

	/** @var callback Callback function to add where conditions */
	private $_where = null;

	/** @var string ORDER BY clause */
	private $_order = null;

	private $_manualAdd = array();


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Add extra options to the list, in addition to any obtained from the database
	 *
	 * @param string $label The label to use for the option
	 * @param string|null $value Value for the option. If not given, the label will be used
	 * @return Options Self for chaining
	 */
	public function add ( $label, $value=null )
	{
		if ( $value === null ) {
			$value = $label;
		}

		$this->_manualAdd[] = array(
			'label' => $label,
			'value' => $value
		);

		return $this;
	}

	/**
	 * Get / set the column(s) to use as the label value of the options
	 *
	 * @param  null|string|string[] $_ null to get the current value, string or
	 *   array to get.
	 * @return Options|string[] Self if setting for chaining, array of values if
	 *   getting.
	 */
	public function label ( $_=null )
	{
		if ( $_ === null ) {
			return $this;
		}
		else if ( is_string($_) ) {
			$this->_label = array( $_ );
		}
		else {
			$this->_label = $_;
		}

		return $this;
	}

	/**
	 * Get / set the LIMIT clause to limit the number of records returned.
	 *
	 * @param  null|number $_ Number of rows to limit the result to
	 * @return Options|string[] Self if setting for chaining, limit if getting.
	 */
	public function limit ( $_=null )
	{
		return $this->_getSet( $this->_limit, $_ );
	}

	/**
	 * Get / set the ORDER BY clause to use in the SQL. If this option is not
	 * provided the ordering will be based on the rendered output, either
	 * numerically or alphabetically based on the data returned by the renderer.
	 *
	 * @param  null|string $_ String to set, null to get current value
	 * @return Options|string Self if setting for chaining, string if getting.
	 */
	public function order ( $_=null )
	{
		return $this->_getSet( $this->_order, $_ );
	}

	/**
	 * Get / set the label renderer. The renderer can be used to combine
	 * multiple database columns into a single string that is shown as the label
	 * to the end user in the list of options.
	 *
	 * @param  null|callable $_ Function to set, null to get current value
	 * @return Options|callable Self if setting for chaining, callable if
	 *   getting.
	 */
	public function render ( $_=null )
	{
		return $this->_getSet( $this->_renderer, $_ );
	}

	/**
	 * Get / set the database table from which to gather the options for the
	 * list.
	 *
	 * @param  null|string $_ String to set, null to get current value
	 * @return Options|string Self if setting for chaining, string if getting.
	 */
	public function table ( $_=null )
	{
		return $this->_getSet( $this->_table, $_ );
	}

	/**
	 * Get / set the column name to use for the value in the options list. This
	 * would normally be the primary key for the table.
	 *
	 * @param  null|string $_ String to set, null to get current value
	 * @return Options|string Self if setting for chaining, string if getting.
	 */
	public function value ( $_=null )
	{
		return $this->_getSet( $this->_value, $_ );
	}

	/**
	 * Get / set the method to use for a WHERE condition if it is to be
	 * applied to the query to get the options.
	 *
	 * @param  null|callable $_ Function to set, null to get current value
	 * @return Options|callable Self if setting for chaining, callable if
	 *   getting.
	 */
	public function where ( $_=null )
	{
		return $this->_getSet( $this->_where, $_ );
	}

	public function leftJoin ( $table, $field1, $operator, $field2 )
	{
		$this->_leftJoin[] = array(
			"table"    => $table,
			"field1"   => $field1,
			"field2"   => $field2,
			"operator" => $operator
		);

		return $this;
	}

	private function _get_where ( $query )
	{
		for ( $i=0 ; $i<count($this->_where) ; $i++ ) {
			if ( is_callable( $this->_where[$i] ) ) {
				$this->_where[$i]( $query );
			}
			else {
				$query->where(
					$this->_where[$i]['key'],
					$this->_where[$i]['value'],
					$this->_where[$i]['op']
				);
			}
		}
	}

	private function _perform_left_join ( $query )
	{
		if ( count($this->_leftJoin) ) {
			for ( $i=0, $ien=count($this->_leftJoin) ; $i<$ien ; $i++ ) {
				$join = $this->_leftJoin[$i];
				$query->join( $join['table'], $join['field1'].' '.$join['operator'].' '.$join['field2'], 'LEFT' );
			}
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */
	
	/**
	 * Execute the options (i.e. get them)
	 *
	 * @param  Database $db Database connection
	 * @return array        List of options
	 * @internal
	 */
	public function exec ( $field, $editor, $http, $fields, $leftJoinIn )
	{
		// If the value is not yet set then set the variable to be the field name
		if ( $this->_value == null) {
			$value = $field->dbField();
		}
		else {
			$value = $this->_value;
		}

		// If the table is not yet set then set the table variable to be the same as editor
		if ( $this->_table == null) {
			$table = $editor->table();
		}
		else {
			$table = $this->_table;
		}

		// If the label value has not yet been set then just set it to be the same as value
		if ( $this->_label == null ) {
			$label = $value;
		}
		else {
			$label = $this->_label[0];
		}

		// Set the database from editor
		$db = $editor->db();

		$formatter = $this->_renderer;

		// We need a default formatter if one isn't provided
		if ( ! $formatter ) {
			$formatter = function ( $row ) {
				return implode(' ', $row);
			};
		}

		// Set up the join variable so that it will fit nicely later
		if(count($this->_leftJoin) > 0){
			$join = $this->_leftJoin[0];
		}
		else {
			$join = $this->_leftJoin;
		}

		// Set up the left join varaible so that it will fit nicely later
		if(count($leftJoinIn) > 0) {
			$leftJoin = $leftJoinIn[0];
		}
		else {
			$leftJoin = $leftJoinIn;
		}

		// Set the query to get the current counts for viewTotal
		$query = $db
			->query('select')
			->table( $table );

		if ( $field->apply('get') && $field->getValue() === null ) {
			$query->get( $value." as value", "COUNT(*) as count");
			$query->group_by( $value);
		}

		// If a join is required then we need to add the following to the query
		if (count($leftJoin) > 0){
			$query->join( $leftJoin['table'], $leftJoin['field1'].' '.$leftJoin['operator'].' '.$leftJoin['field2'], 'LEFT' );
		}

		// Construct the where queries based upon the options selected by the user
		if( isset($http['searchPanes']) ) {
			foreach ($fields as $fieldOpt) {
				if( isset($http['searchPanes'][$fieldOpt->name()])){
					$query->where( function ($q) use ($fieldOpt, $http) {
						for($j=0 ; $j<count($http['searchPanes'][$fieldOpt->name()]) ; $j++){
							$q->or_where( $fieldOpt->dbField(), '%'.$http['searchPanes'][$fieldOpt->name()][$j].'%', 'like' );
						}
					});
				}
			}
		}

		$res = $query
			->exec()
			->fetchAll();

		// Get the data for the pane options
		$q = $db
			->query('select')
			->table( $table )
			->get( $label." as label", $value." as value", "COUNT(*) as total" )
			->group_by( $value )
			->where( $this->_where );

		// If a join is required then we need to add the following to the query
		if (count($join) > 0){
			$q->join( $join['table'], $join['field1'].' '.$join['operator'].' '.$join['field2'], 'LEFT' );
		}

		if ( $this->_order ) {
			// For cases where we are ordering by a field which isn't included in the list
			// of fields to display, we need to add the ordering field, due to the
			// select distinct.
			$orderFields = explode( ',', $this->_order );

			for ( $i=0, $ien=count($orderFields) ; $i<$ien ; $i++ ) {
				$field = strtolower( $orderFields[$i] );
				$field = str_replace( ' asc', '', $field );
				$field = str_replace( ' desc', '', $field );
				$field = trim( $field );

				if ( ! in_array( $field, $fields ) ) {
					$q->get( $field );
				}
			}

			$q->order( $this->_order );
		}

		if ( $this->_limit !== null ) {
			$q->limit( $this->_limit );
		}

		$rows = $q
			->exec()
			->fetchAll();

		// Create the output array
		$out = array();

		for ( $i=0, $ien=count($rows) ; $i<$ien ; $i++ ) {
			$set = false;
			for( $j=0 ; $j<count($res) ; $j ++) {
				if($res[$j]['value'] == $rows[$i]['value']){
					$out[] = array(
						"label" => $rows[$i]['label'],
						"total" => $rows[$i]['total'],
						"value" => $rows[$i]['value'],
						"count" => $res[$j]['count']
					);
					$set = true;
				}
			}
			if(!$set) {
				$out[] = array(
					"label" => $rows[$i]['label'],
					"total" => $rows[$i]['total'],
					"value" => $rows[$i]['value'],
					"count" => 0
				);
			}
			
		}

		// Stick on any extra manually added options
		if ( count( $this->_manualAdd ) ) {
			$out = array_merge( $out, $this->_manualAdd );
		}

		// Only sort if there was no SQL order field
		if ( ! $this->_order ) {
			usort( $out, function ( $a, $b ) {
				return is_numeric($a['label']) && is_numeric($b['label']) ?
					($a['label']*1) - ($b['label']*1) :
					strcmp( $a['label'], $b['label'] );
			} );
		}

		return $out;
	}
}
	