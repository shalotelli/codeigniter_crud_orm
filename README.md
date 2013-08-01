CodeIgniter CRUD/ORM MY_Model
====================

Base model with CRUD functionality powered by CodeIgniter's query builder.

Naming Conventions
------------------
- This class will try to guess the name of the table to use, by finding the plural of the class name. For instance:
```PHP
  class Post_model extends MY_Model { }
```
  ...will guess a table name of posts.

- If you need to set it to something else, you can use the method:
```PHP
  $this->table('table_name');
````

- Some of the CRUD functions also assume that your primary key ID column is called 'id'. 
  You can overwrite this functionality by using the method:
```PHP
  $this->primary_key('primary_id');
```

Relationships
-------------
- MY_Model supports for basic belongs_to and has_many relationships. 
  These relationships are easy to define:
```PHP
  class Post_model extends MY_Model
  {
  	public $belongs_to = array('author');
  	public $has_many = array('comments');
  }
```

- It will assume that a MY_Model API-compatible model with the singular relationship's name has been defined. 
  By default, this will be relationship_model. The above example, for instance, would require two other models:
```PHP
  class Author_model extends MY_Model { }
  class Comment_model extends MY_Model { }
```

- If you'd like to customise this, you can pass through the model name as a parameter:
```PHP
  class Post_model extends MY_Model
  {
  	public $belongs_to = array('author' => array('model' => 'author_m'));
  	public $has_many = array('comments' => array('model' => 'model_comments'));
  }
```

- You can then access your related data using the with() method:
```PHP
  $post = $this->post_model->with('author')->with('comments')->find(1);
 ```

- The related data will be embedded in the returned value from get:
```PHP
  echo $post->author->name;
```

- Separate queries will be run to select the data, so where performance is important, 
  a separate JOIN and SELECT call is recommended.

Arrays vs Objects
-----------------
- By default, MY_Model is setup to return objects using CodeIgniter's QB's row() and result() methods. 
  If you'd like your calls to use the array methods, you can set the $return_type variable to array.
```PHP
  this->return_type('array');
```

Soft Delete
-----------
- By default, the delete mechanism works with an SQL DELETE statement. 
  However, you might not want to destroy the data, you might instead want to perform a 'soft delete'.

- If you enable soft deleting, the deleted row will be marked as deleted rather than actually 
  being removed from the database.

- We can enable soft delete by setting the $this->soft_delete key:
```PHP
  $this->soft_delete(true);
```

- By default, MY_Model expects a TINYINT or INT column named deleted. You can customise this using the method:
```PHP
  $this->soft_delete_key('key');
```

- Now, when you make a call to any of the get_ methods, a constraint will be added to not output deleted columns:
```PHP
  $this->book_model->get_by('user_id', 1);
```
```SQL
  SELECT * FROM books WHERE user_id = 1 AND deleted = 0
```

Database Connection
-------------------
- The class will automatically use the default database connection, and even load it for you if you haven't yet.

- You can specify a database connection on a per-model basis with the db_group method. 
  This is equivalent to calling ```PHP$this->db->database($this->db_group, TRUE).```
```PHP
  $this->db_group('group_name');
```