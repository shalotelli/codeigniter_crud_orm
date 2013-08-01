<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Base model with CRUD functionality powered by CodeIgniter's query builder.
 *
 * Naming Conventions
 * ------------------
 * - This class will try to guess the name of the table to use, by finding the plural of the class name.
 * 	 For instance:
 *   class Post_model extends MY_Model { }
 *   ...will guess a table name of posts.
 *
 * - If you need to set it to something else, you can use the method:
 *   $this->table('table_name');
 *
 * - Some of the CRUD functions also assume that your primary key ID column is called 'id'. 
 *   You can overwrite this functionality by using the method:
 *   $this->primary_key('primary_id');
 *
 * 
 * Relationships
 * -------------
 * - MY_Model supports for basic belongs_to and has_many relationships. 
 *   These relationships are easy to define:
 *   class Post_model extends MY_Model
 *   {
 *   	public $belongs_to = array('author');
 *   	public $has_many = array('comments');
 *   }
 *
 * - It will assume that a MY_Model API-compatible model with the singular relationship's name has been defined. 
 *   By default, this will be relationship_model. The above example, for instance, would require two other models:
 *   class Author_model extends MY_Model { }
 *   class Comment_model extends MY_Model { }
 *
 * - If you'd like to customise this, you can pass through the model name as a parameter:
 *   class Post_model extends MY_Model
 *   {
 *   	public $belongs_to = array('author' => array('model' => 'author_m'));
 *   	public $has_many = array('comments' => array('model' => 'model_comments'));
 *   }
 *
 * - You can then access your related data using the with() method:
 *   $post = $this->post_model->with('author')->with('comments')->find(1);
 *
 * - The related data will be embedded in the returned value from get:
 *   echo $post->author->name;
 *
 * - Separate queries will be run to select the data, so where performance is important, 
 *   a separate JOIN and SELECT call is recommended.
 *
 * 
 * Arrays vs Objects
 * -----------------
 * - By default, MY_Model is setup to return objects using CodeIgniter's QB's row() and result() methods. 
 *   If you'd like your calls to use the array methods, you can set the $return_type variable to array.
 *   this->return_type('array');
 *
 *
 * Soft Delete
 * -----------
 * - By default, the delete mechanism works with an SQL DELETE statement. 
 *   However, you might not want to destroy the data, you might instead want to perform a 'soft delete'.
 *
 * - If you enable soft deleting, the deleted row will be marked as deleted rather than actually 
 *   being removed from the database.
 *
 * - We can enable soft delete by setting the $this->soft_delete key:
 *   $this->soft_delete(true);
 *
 * - By default, MY_Model expects a TINYINT or INT column named deleted. You can customise this using the method:
 *   $this->soft_delete_key('key');
 *
 * - Now, when you make a call to any of the get_ methods, a constraint will be added to not output deleted columns:
 *   $this->book_model->get_by('user_id', 1);
 *   SELECT * FROM books WHERE user_id = 1 AND deleted = 0
 *
 * 
 * Database Connection
 * -------------------
 * - The class will automatically use the default database connection, and even load it for you if you haven't yet.
 *
 * - You can specify a database connection on a per-model basis with the db_group method. 
 *   This is equivalent to calling $this->db->database($this->db_group, TRUE).
 *   $this->db_group('group_name');
 *
 * 
 * @version v1.0.0
 */
class MY_Model extends CI_Model {

	/**
	 * Models default database table. Automatically guessed by 
	 * pluralising model name
	 * 
	 * @var string
	 */
	protected $table;

	/**
	 * Specify a database group to manually connect this model
	 * to the specified DB. Either a group name defined in 
	 * database.php, or config array of the same format. If empty, 
	 * default DB used
	 * 
	 * @var array
	 */
	protected $db_group;

	/**
	 * The database connection object. Will be set to the default
	 * connection unless $this->_db_group is specified. This allows
	 * individual models to use different DBs without overwriting
	 * CI's global $this->db connection.
	 *
	 * @var object
	 */
	public $database;

	/**
	 * Models default primary key or unique identifier
	 * 
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Support for soft deletes
	 *
	 * @var boolean
	 */
	protected $soft_delete = false;

	/**
	 * Tables soft delete key
	 * 
	 * @var string
	 */
	protected $soft_delete_key = 'deleted';

	/**
	 * Results are returned as objects by default
	 * 
	 * @var string
	 */
	protected $return_type = 'object';

	/**
	 * Relationship buffer array to hold which information to join on to result
	 * 
	 * @var array
	 */
	protected $with = array();

	/**
	 * Relationship array specifying tables this models table belongs to.
	 * Use flat strings for defaults or string => array to customise the 
	 * class name and primary key
	 * 
	 * @var array
	 */
	public $belongs_to = array();

	/**
	 * Relationship array specifying tables that belong to this models tabl.
	 * Use flat strings for defaults or string => array to customise the 
	 * class name and primary key
	 * 
	 * @var array
	 */
	public $has_many = array();

	/**
     * An array of validation rules. This needs to be the same format
     * as validation rules passed to the Form_validation library.
     * 
	 * @var array
	 */
	public $validation_rules = array();

	/* --------------------------------------------------------------
	 * GENERIC METHODS
	 * -------------------------------------------------------------- */

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// used to pluralise database model
		$this->load->helper('inflector');

		// guess the table name by pluralising the model name
		$this->table = plural(preg_replace('/(_model)?$/', '', strtolower(get_class($this))));

		$this->_set_database();
	}

	/* --------------------------------------------------------------
	 * CRUD INTERFACE
	 * -------------------------------------------------------------- */

	/**
	 * Insert row into table
	 * 
	 * @param  array   $data Associative array of data
	 * @return integer       Row ID
	 */
	public function create($data)
	{
		// loop through data and make sure field exists, otherwise ignore $val
		foreach((array) $data as $key => $val) {
			if($this->database->field_exists($key, $this->table)) {
				$this->database->set($key, $val);
			}
		}

		// insert data
		$this->database->insert($this->table);

		// return new ID
		return $this->database->insert_id();
	}

	/**
	 * Multiple inserts using transaction
	 * 
	 * @param  array $data Associative array of each row data
	 * @return array       Row IDs or False on failure
	 */
	public function batch_create($data)
	{
		// buffer to hold created IDs
		$ids = array();

		// start transaction
		$this->database->trans_start();

		// insert each row
		foreach($data as $key => $row) {
			$ids[] = $this->create($row);
		}

		// end transaction
		$this->database->trans_complete();

		// return array of id's or false
		if(! $this->database->trans_status()) {
			return false;
		} else {
			return $ids;
		}
	}

	/**
	 * Select data from table
	 * 
	 * @param  array   $where Associative array of data
	 * @return object         Returned rows
	 */
	public function get($where=array())
	{
		// if soft deleting, look for non-deleted rows
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		// set where clause
		$this->_set_where($where);

		// get results
		$result = $this->database->get($this->table)
								 ->{$this->_return_type(true)}();

		// if relation specified, join rows
		if(! empty($this->with)) {
			foreach($result as $key => &$row) {
				$row = $this->relate($row);
			}

			// reset array
			$this->with = array();
		}

		return $result;
	}

	/**
	 * Find a particular Row by ID
	 * 
	 * @param  integer $id Row ID
	 * @return object      Returned row
	 */
	public function find($id)
	{
		// if soft deleting, look for non-deleted rows
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		// get result
		$row = $this->database->where($this->primary_key, $id)
							  ->get($this->table)
							  ->{$this->_return_type()}();

		// if relation specified, join rows
		if(! empty($this->with)) {
			$row = $this->relate($row);

			// reset array
			$this->with = array();
		}

		return $row;
	}

	/**
	 * Return all rows in table
	 * 
	 * @return object Returned rows
	 */
	public function all()
	{
		// if soft deleting, look for non-deleted rows
		if($this->soft_delete) {
			$this->database_where($this->soft_delete_key, false);
		}

		return $this->database->get($this->table)->{$this->_return_type(true)}();
	}

	/**
	 * Update row in table
	 * 
	 * @param  integer $id   Row ID
	 * @param  array   $data Associative array of data
	 * @return null
	 */
	public function update($id, $data)
	{
		// loop through data and make sure field exists, otherwise ignore $val
		foreach((array) $data as $key => $val) {
			if($this->database->field_exists($key, $this->table)) {
				$this->database->set($key, $val);
			}
		}

		return $this->database->where($this->primary_key, $id)->update($this->table);
	}

	/**
	 * Update many records, based on an array of IDs.
	 * 
	 * @param  array   $ids  Array of IDs
	 * @param  array   $data Associative array of data
	 * @return boolean       Success
	 */
	public function batch_update($ids, $data)
	{
		return $this->database->where_in($this->primary_key, $ids)
							  ->set($data)
							  ->update($this->table);
	}

	/**
	 * Updated a record based on an arbitrary WHERE clause.
	 * 
	 * @return boolean Success
	 */
	public function update_where()
	{
		$args = func_get_args();
		$data = array_pop($args);
		$this->_set_where($args);

		return $this->database->set($data)->update($this->table);
	}

	/**
	 * Update all records
	 * 
	 * @param  array $data Associative array of data
	 * @return boolean     Success
	 */
	public function update_all($data)
	{
		return $this->database->set($data)->update($this->table);
	}

	/**
	 * Delete row from table
	 * 
	 * @param  integer $id Row ID
	 * @return null
	 */
	public function delete($id)
	{
		$this->database->where($this->primary_key, $id);

		if($this->soft_delete) {
			return $this->database->update($this->table, array($this->soft_delete_key => true));
		} else {
			return $this->database->delete($this->table);
		}
	}

	/**
	 * Delete a row from the database table by an arbitrary WHERE clause
	 * 
	 * @return boolean Success
	 */
	public function delete_where()
	{
		$where = func_get_args();
		$this->_set_where($where);

		if($this->soft_delete) {
			return $this->database->update($this->table, array($this->soft_delete_key => true));
		} else {
			return $this->database->delete($this->table);
		}
	}

	/**
	 * Delete many rows from the database table by multiple IDs
	 * 
	 * @param  array   $ids Array of IDs
	 * @return boolean 		Success
	 */
	public function batch_delete($ids)
	{
		$this->database->where_in($this->primary_key, $ids);

		if($this->soft_delete) {
			return $this->database->update($this->table, array($this->soft_delete_key => true));
		} else {
			return $this->database->delete($this->table);
		}
	}

	/* --------------------------------------------------------------
	 * UTILITY METHODS
	 * -------------------------------------------------------------- */

	/**
	 * Get row count
	 *
	 * @param  array   $where Where clause
	 * @return integer 		  Row count
	 */
	public function count($where=array())
	{
		return $this->database->where($where)
							  ->get($this->table)
							  ->num_rows();
	}

	/**
	 * Select Min
	 * 
	 * @param  string $field Field to search
	 * @param  string $alias Result alias
	 * @return object        Returned result
	 */
	public function min($field, $alias='')
	{
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		return $this->database->select_min($field, $alias)
							  ->get($this->table)
							  ->{$this->_return_type()}();
	}

	/**
	 * Select Max
	 * 
	 * @param  string $field Field to search
	 * @param  string $alias Result alias
	 * @return object        Returned result
	 */
	public function max($field, $alias='')
	{
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		return $this->database->select_max($field, $alias)
							  ->get($this->table)
							  ->{$this->_return_type()}();
	}

	/**
	 * Select Average
	 * 
	 * @param  string $field Field to search
	 * @param  string $alias Result alias
	 * @return object        Returned result
	 */
	public function avg($field, $alias='')
	{
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		return $this->database->select_avg($field, $alias)
							  ->get($this->table)
							  ->{$this->_return_type()}();
	}

	/**
	 * Select Sum
	 * 
	 * @param  string $field Field to search
	 * @param  string $alias Result alias
	 * @return object        Returned result
	 */
	public function sum($field, $alias='')
	{
		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		return $this->database->select_sum($field, $alias)
							  ->get($this->table)
							  ->{$this->_return_type()}();
	}

	/**
	 * Truncates the table
	 * 
	 * @return boolean Success
	 */
	public function truncate()
	{
		return $this->database->truncate($this->table);
	}

	/**
	 * Return the next auto increment of the table
	 * 
	 * @return integer Next ID
	 */
	public function next_id()
	{
		return (int) $this->database->select('AUTO_INCREMENT')
									->from('information_schema.TABLES')
									->where('TABLE_NAME', $this->table)
									->where('TABLE_SCHEMA', $this->database->database)
									->get()
									->row()
									->AUTO_INCREMENT;
	}

	/**
	 * Retrieve and generate a form_dropdown friendly array
	 * 
	 * @return array Results array of option/values
	 */
	public function dropdown()
	{
		// get function arguments
		$options = array();
		$args = func_get_args();

		if(count($args)==2) {
			list($key, $val) = $args;
		} else {
			$key = $this->primary_key;
			$value = $args[0];
		}

		if($this->soft_delete) {
			$this->database->where($this->soft_delete_key, false);
		}

		$result = $this->database->select($array($key, $val))
								 ->get($this->table)
								 ->result();

		foreach($result as $row) {
			$options[$row->{$key}] = $row->{$val};
		}

		return $options;
	}

	/* --------------------------------------------------------------
	 * VALIDATION
	 * -------------------------------------------------------------- */

	/**
	 * Set validation rules
	 *
	 * @return boolean Success or failure
	 */
	public function set_validation()
	{
		if(empty($this->validation_rules)) {
			return false;
		}

		$this->load->library('form_validation');
		$this->load->library('jquery_validation');
		
		$this->form_validation->set_rules($this->validation_rules);
		$this->jquery_validation->set_rules($this->validation_rules);

		return true;
	}

	/* --------------------------------------------------------------
	 * GETTERS / SETTERS
	 * -------------------------------------------------------------- */

	/**
	 * Getter/setter for table name
	 *
	 * @param  string $table Table name
	 * @return string 		 Table name
	 */
	public function table($table='')
	{
		if($table=='') {
			return $this->table;
		} else {
			$this->table = $table;
		}
	}

	/**
	 * Getter/setter for primary key column
	 *
	 * @param  string  $primary_key Column name
	 * @return string 				Column name
	 */
	public function primary_key($key='')
	{
		if($key=='') {
			return $this->primary_key;
		} else {
			$this->primary_key = $key;
		}
	}

	/**
	 * Getter/setter whether to do soft deletes
	 *
	 * @param  boolean $soft_delete Soft delete flag
	 * @return boolean 				Soft delete flag
	 */
	public function soft_delete($soft_delete='')
	{
		if($soft_delete=='') {
			return $this->soft_delete;
		} else {
			$this->soft_delete = $soft_delete;
		}
	}

	/**
	 * Getter/setter for soft delete column name
	 * 
	 * @param  string $soft_delete_key Soft delete column
	 * @return string                  Soft delete column
	 */
	public function soft_delete_key($soft_delete_key='')
	{
		if($soft_delete_key=='') {
			return $this->soft_delete_key;
		} else {
			$this->soft_delete_key = $soft_delete_key;
		}
	}

	/**
	 * Getter/Setter for return type
	 * 
	 * @param  string $return_type Array or Object
	 * @return string              Return type
	 */
	public function return_type($return_type='')
	{
		if($return_type=='') {
			return $this->return_type;
		} else {
			$this->return_type = $return_type;
		}
	}

	/* --------------------------------------------------------------
	 * RELATIONSHIPS
	 * -------------------------------------------------------------- */

	/**
	 * Add a relationship
	 * 
	 * @param  string $relationship Table to relate to
	 * @return object               DB object to enable chaining
	 */
	public function with($relationship)
	{
		$this->with[] = $relationship;

		return $this;
	}

	/**
	 * Get all requested relation table data
	 * 
	 * @param  object $row Row to add data on to
	 * @return object      Shiny new row
	 */
	protected function relate($row)
	{
		// loop through belongs_to array
		foreach($this->belongs_to as $key => $val) {
			// if val is a string, create an options method
			if(is_string($val)) {
				$relationship = $val;
				$options = array('primary_key' => $val.'_id', 'model' => $val.'_model');
			} else {
				$relationship = $key;
				$options = $val;
			}

			// find all relationships that are called
			if(in_array($relationship, $this->with)) {
				// load external model
				$this->load->model($options['model']);

				// check whether object or array result requested and add relationship results on accordingly
				if(is_object($row)) {
					$row->{$relationship} = $this->{$options['model']}->find($row->{$options['primary_key']});
				} else {
					$row[$relationship] = $this->{$options['model']}->find($row[$options['primary_key']]);
				}
			}
		}

		// loop through has_many array
		foreach($this->has_many as $key => $val) {
			// if val is a string, create an options method
			if(is_string($val)) {
				$relationship = $val;
				$options = array('primary_key' => singular($this->table).'_id', 'model' => singular($val).'_model');
			} else {
				$relationship = $key;
				$options = $val;
			}

			// find all relationships that are called
			if(in_array($relationship, $this->with)) {
				$this->load->model($options['model']);

				// check whether object or array result requested and add relationship results on accordingly
				if(is_object($row)) {
					$row->{$relationship} = $this->{$options['model']}->get($row->{$this->primary_key});
				} else {
					$row[$relationship] = $this->{$options['model']}->get($row[$this->primary_key]);
				}
			}
		}

		return $row;
	}

	/* --------------------------------------------------------------
	 * QUERY BUILDER DIRECT ACCESS METHODS
	 * -------------------------------------------------------------- */

	/**
	 * Wrapper for $this->_database->order_by()
	 * 
	 * @param  array $fields  Associative array of fields or string
	 * @param  string $order  ASC or DESC
	 * @return [type]         DB object to enable chaining
	 */
	public function order_by($fields, $order='ASC')
	{
		if(is_array($fields)) {
			foreach($fields as $key => $val) {
				$this->database->order_by($key, $val);
			}
		} else {
			$this->database->order_by($fields, $order);
		}

		return $this;
	}

	/**
	 * Wrapper for $this->database->limit()
	 * 
	 * @param  integer  $limit Row limit
	 * @param  integer $offset Results start point
	 * @return [type]          DB object to enable chaining
	 */
	public function limit($limit, $offset=0)
	{
		$this->database->limit($limit, $offset);
		return $this;
	}

	/* --------------------------------------------------------------
	 * PRIVATE METHODS
	 * -------------------------------------------------------------- */

	/**
	 * Create database variable to allow multiple databases to be loaded
	 * without overloading the main db variiable
	 */
	private function _set_database()
	{
		if($this->db_group!==NULL) {
			$this->database = $this->load->database($this->db_group, true, true);
		} else {
			if(! isset($this->db) OR is_object($this->db)) {
				$this->load->database('', false, true);
			}
		}

		$this->database = $this->db;
	}

	/**
	 * Figure out whether to return an array or object
	 * 
	 * @param  boolean $multi TRUE = result, FALSE = row
	 * @return string         Function string
	 */
	private function _return_type($multi=false) {
		$method = ($multi) ? 'result' : 'row';
		return $this->return_type == 'array' ? $method.'_array' : $method;
	}

	/**
	 * Smart where
	 * 
	 * @param string|array $where Where clause
	 */
	private function _set_where($where) {
		if(! empty($where)) {
			if(count($where)==1) {
				$this->database->where($where[0]);
			} else {
				$this->database->where($where[0], $where[1]);
			}
		}
	}

}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */