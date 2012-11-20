<?php
/**
 * @package mvcstarter
 * @author Andrew Meredith <andymeredith@gmail.com>
 */

namespace MVCStarterPlugin\Lib\Common;

use MVCStarterPlugin\Lib\AppRegistry;
use MVCStarterPlugin\Lib\SessionRegistry;
use MVCStarterPlugin\Lib\RequestRegistry;
use MVCStarterPlugin\Lib\Util\Inflector;
use MVCStarterPlugin\Lib\Ideals\Serializable;

/**
 * Provides the base functionality that any application model will need
 * 
 * Functions as the generic serializable model. All models in your application should
 * be subclassed from <code>Model</code>. This class takes care of most of the "heavy lifting"
 * and object-relational mapping that will need to be done in your application. Feel
 * free to override any of the methods implemented from <code>Serializable</code> to
 * customize the way that your objects are saved to the database.
 * 
 * All attributes that will be saved to the database must be named by their database
 * column. Any attribute starting with an underscore will not be recorded in the
 * database - it will be considered for internal use only.
 * 
 * For example, a model with three properties, <code>$name</code>, <code>$age</code>,
 * and <code>$_logged_in</code> will save <code>$name</code> and <code>$age</code> to
 * the database but will leave <code>$_logged_in</code> alone.
 * 
 * Any object that needs to be accessed via a web form needs to declare a static
 * $_attr_accessible property with an array of the properties that may be saved. If this
 * property is not present, is is assumed that <em>any</em> property may be set.
 * This is <strong>DANGEROUS</strong> and leaves your application wide open to hacking.
 * 
 * The serialization functionality assumes several things about the data model:
 * 1. All tables will have a primary key named 'id'
 * 2. All foreign keys will be named as "{$this->belongs_to}_id"
 * 3. No many-to-many relationships exist in the database.
 * Optional configuration to change these assumptions will be added in a future version.
 */
abstract class Model implements Serializable
{
    /** @property wbdb $_db the global $wbdb instance */
    private $_db; // We can swap with a WPWrapper class, or a more specialized version of wpdb

    /** @property Array a collection of the attributes that may be set via web form */
    static protected $_attr_accessible = array();

    /**
     * @property string The parent model in a 1-to-1 or 1-to-many relationship.
     * If this is specified in a model, then <em>this</em> model ought to have a
     * <code>$PARENT_id</code> field.
     */
    static protected $_belongs_to;

    /** 
     * @property Array a collection of child models. It is assumed that the <em>child</em>
     * will have a <code>$PARENT_id</code> field.
     */
    static protected $_has = array();

    /** @property int $_image_upload_index ??? I have no idea what this is */
    static protected $_image_upload_index = 0;

    /** @property int $id The unique identifier of this object in the database */
    protected $id;

    /**
     * Constructs a new instance of the Model
     * 
     * @throws \Exception when global WordPress database class ($wpdb) cannot be found
     */
    public function __construct($wpdb = null) {
        if (!$wpdb) {
            global $wpdb;
        }
        if (!$wpdb) {
            throw new \Exception("WordPress database not available!");
        }
        $this->$_db = &$wpdb;

        $reg = AppRegistry::instance();
        $this->app_name = $reg->get('name');
    }

    /**
     * Returns the id property
     * 
     * @return int the id of this object, 0 if it is has not been intserted into the database yet
     */
    public function getId() {
        return ($this->id) ? $this->id : 0;
    }
    
    /**
     * Sets the id property
     * 
     * @param int the id to set
     * @throws \InvalidArgumentException
     */
    public function setId( $id ) {
        if (is_int( (int)$id )) {
            $this->id = (int)$id;
        } else {
            throw new \InvalidArgumentException("Invalid ID supplied. Expected an integer, but got '$id'");
        }
    }

    /**
     * Creates a new model from the array given
     * 
     * Creates a new instance of the <code>Model</code> subclass with its attributes set to the values
     * specified in the $params parameter. This is most useful when used to build a new record from
     * a web form. If no parameter is given, it will default to $_POST[MODEL_NAME].
     * 
     * Is $_attr_accessible is set on this class, this function will respect it and will not attempt to set
     * any attribute not in the accessible array. If this parameter is not specified (or empty), it will
     * attempt to set any and all parameters given.
     * 
     * Note that just creating a new object with this function does not save it. If you want to save the
     * record to database, you will need to call save() on the returned model.
     * 
     * @param array $params the array of attributes to be used to build a new object,
     * defaults to $_POST[class name].
     * @return Model a new model instance
     */
    static public function from($params = null) {
        $params = ($params) ? $params : $_POST;
        $app_slug = Inflector::underscore($this->app_name);

        // Allow client plug-ins to modify form data
        if ($_FILES) {
            $files = apply_filter("{$app_slug}_process_file_uploads", $_FILES);
        }if ($params) {
            $fields = apply_filter("{$app_slug}_process_form_fields", $params);
        }

        $classname = Inflector::denamespace(get_called_class());

        if ($params === null) {
            $params = $_POST[strtolower($classname)];
        }

        $updload_dir = AppRegistry::instance()->get('uploads_dir');
        if (!$updload_dir) {
            throw new \Exception("No upload directory specified.");
        }

        $instance = (isset($params['id']) && !empty($params['id']))
            ? $class::find($params['id'])
            : new $class();

        // TODO: Handle file uploads!

        foreach ($params as $attr => $value) {
            // Don't worry: the __set() magic method will respect self::attr_accessible
            $instance->$attr = $value;
        }

        return $instance;
    }


    /**
     * Gets last inserted record
     * 
     * @return Model an instance of the last model of this type added to the database
     */
    static public function last() {
        global $wpdb;
        if (!$wpdb) {
            throw new \Exception("WordPress database connection could not be established");
        }

        $class = get_called_class();
        $classname = Inflector::denamespace(get_called_class());

        $sql = 'SELECT * FROM ' . Inflector::tablize($classname) . ' ORDER BY id DESC LIMIT 1';
        $row = $wpdb->get_row($sql, ARRAY_A);

        $instance = new $class();
        
        foreach (self::getPersistentAttrubutes() as $attr) {
            $instance->$attr = $row[$attr];
        }

        return $instance;
    }

    /**
     * Gets all records
     * 
     * Gets a Collection object, which is an iterable database cursor that will fetch a given number
     * of records at a time and will instantiate them as instances of the given Model one at a time.
     * 
     * @param int $preload optional number of records to fetch from the database at a time (affects performance only, not behaviour).
     * @return Collection an array-like collection of models that are lazily instantiated when needed
     */
    static public function findAll($preload = 20) {
        return new Collection(get_called_class(), $preload);
    }

    /**
     * Gets records matching given criteria
     * 
     * @param string $col the column to use to match the criteria
     * @param string $relation the operator to use to compare the column to the value.
     * @param string $value the value to use for matching
     */
    static public function findWhere($col, $relation, $value) {
        $models = array();

        // Sanitize column name
        $col        = strtolower(preg_replace('/[^\w]/', '', $col));
        if (in_array($col, array(
            'select', 'from', 'where', '...FILL IN WITH MORE KEYWORDS...'
        ))) {
            throw new \InvalidArgumentException("Cannot use an SQL keyword for column name");
        }

        // Sanitize relation - default to "=" if an invalid relation is passed in
        $relation = strtolower($relation);
        if (!in_array($relation, array(
            '<', '<=', '=', '>=', 'like', 'not like'
        ))) {
            $relation = '=';
        }

        // Sanitize value
        // TODO

        $classname  = Inflector::denamespace(get_called_class());

        $sql        =     'SELECT ' . implode(', ', self::getPersistentAttrubutes())
                        . ' FROM ' . Inflector::tablize($classname)
                        . " WHERE $col $relation $value";

        try {
            $db = self::getDbHandle();
            $sth = $db->prepare($sql);
            $sth->bindParam(':value', $value);
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            $sth->execute();

            $class = get_called_class();
            while($row = $sth->fetch()) {
                $model = new $class();

                foreach (self::getPersistentAttrubutes() as $attr) {
                    $model->$attr = $row[$attr];
                }

                $models[] = $model;
            }
            
        } catch (\PDOException $e) {
            echo 'Could not connect to database: ' . $e->getMessage();
        }
        
        return $models;
    }

    static public function find($id) {
        $models = self::findWhere('id', '=', $id);
        return $models[0];
    }

    static public function findBy($col, $value) {
        return self::findWhere($col, '=', $value);
    }

    public function save($cascade = false) {
        if ($this->isNewRecord()) {
            $this->create();
        } else {
            $this->update();
        }
    }

    public function create() {
        $classname  = get_class($this);

        $columns    = self::getPersistentAttrubutes();

        // Filter out ID parameter
        foreach ($columns as $index => $col) {
            if ($col == 'id') {
                unset($columns[$index]);
            }
        }

        $named_placeholders = array_map(function($col) {
            return ":{$col}";
        }, $columns);

        $columns_list = implode(', ', $columns);
        $named_placeholders = implode(', ', $named_placeholders);

        $sql        =     'INSERT INTO '
                        . strtolower(\PhpQuizzes\Base\Inflect::pluralize(self::deNamespace($classname)))
                        . " ( $columns_list ) VALUES ( $named_placeholders )";

        try{
            $sth = $this->_db->prepare($sql);
        
            foreach ($columns as $col) {
                $sth->bindParam(':' . $col, $this->$col);
            }
            
            if ( $sth->execute() ) {
                $id = $this->_db->lastInsertId();
                $this->id = $id;
                return $id;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            echo "Could not create record. Reason: {$e->getMessage()}\n";
        }
    }

    public function update() {
        $classname  = get_class($this);

        $columns    = self::getPersistentAttrubutes();

        // Filter out ID parameter
        foreach ($columns as $index => $col) {
            if ($col == 'id') {
                unset($columns[$index]);
            }
        }

        // Build "SET" portion of query
        $set_str    = array_map(function($col) {
            return "{$col} = :{$col}";
        }, $columns);

        $set_str = implode(', ', $set_str);

        $sql        =     'UPDATE ' . \PhpQuizzes\Base\Inflect::pluralize(self::deNamespace($classname))
                        . " SET {$set_str} WHERE id = :id";

        try{
            $sth = $this->_db->prepare($sql);
            foreach (self::getPersistentAttrubutes() as $col) {
                $sth->bindParam(':' . $col, $this->$col);
            }
            
            if ( $sth->execute() ) {
                return $this->id;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            echo "Could not create record. Reason: {$e->getMessage()}\n";
        }
    }

    static public function destroy($id, $cascade = true) {
        $id         = strtolower(preg_replace('/[^\d]/', '', $id));
        if (strlen($id) < 1) {
            return false;
        }

        $class  = get_called_class();

        try {
            $db = self::getDbHandle();
            $sth = $db->prepare('DELETE FROM ' . \PhpQuizzes\Base\Inflect::pluralize(strtolower(self::deNamespace($class)))
                                . ' WHERE id = :id');
            $sth->bindParam(':id', $id);

            if (!$sth->execute()) {
                return false;
            }

            // Cascade deletions to child records
            if ($cascade) {
                $classname_db = strtolower(self::deNamespace($class));

                foreach ($class::$_has as $modelname) {
                    $modelname_db = \PhpQuizzes\Base\Inflect::pluralize($modelname);
                    $sql = "DELETE FROM {$modelname_db} WHERE {$classname_db}_id = :id";
                    $sth = $db->prepare($sql);
                    $sth->bindParam(':id', $id);
                    if (!$sth->execute()) {
                        return false;
                    }
                }
            }

            $sth = null;
        } catch (\PDOException $e) {
            echo "Could not delete record with ID, $id. Reason: {$e->getMessage()}\n";
        }
    }


    static public function getPersistentAttrubutes() {
        $attr = array_keys(get_class_vars(get_called_class()));
        return array_filter($attr, function($elem) {
            return $elem[0] !== '_';
        });
    }

    public function isNewRecord() {
        return ($this->id == null);
    }

    public function getParent() {
        $instance_self      = get_called_class();
        $id                 = $this->id;
        $child_class        = get_class($this);
        $child_classname    = strtolower($instance_self::deNamespace($child_class));
        $parent_classname   = $instance_self::$_belongs_to;
        $parent_class       = $instance_self::modelQualify($parent_classname);

        $child_resource = \PhpQuizzes\Base\Inflect::pluralize($child_classname);
        $parent_resource = \PhpQuizzes\Base\Inflect::pluralize($parent_classname);

        $sql = "SELECT {$parent_resource}.*
                FROM {$child_resource}
                JOIN {$parent_resource} ON ({$child_resource}.{$parent_classname}_id = {$parent_resource}.id)
                WHERE {$child_resource}.id = :id";

        try {
            $sth = $this->_db->prepare($sql);
            $sth->bindParam(':id', $id);
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            $sth->execute();

            $row = $sth->fetch();

            if (empty($row)) {
                return false;
            }

            $instance = new $parent_class();

            foreach ($parent_class::getPersistentAttrubutes() as $attr) {
                $instance->$attr = $row[$attr];
            }

            return $instance;
            
        } catch (\PDOException $e) {
            echo 'Could not connect to database: ' . $e->getMessage();
        }

    }

    // public function getChildren($model) {
    //  $children = array();
    //  $id                 = $this->id;
    //  $parent_classname   = strtolower(self::deNamespace(get_class($this)));
    //  $child_resource     = \PhpQuizzes\Base\Inflect::pluralize(strtolower($model));
    //  $child_class        = self::modelQualify($model);

    //  $sql = "SELECT {$child_resource}.*
    //          FROM {$child_resource}
    //          WHERE {$parent_classname}_id = :id";

    //  try {
      //        $sth = $this->_db->prepare($sql);
      //        $sth->bindParam(':id', $id);
      //        $sth->setFetchMode(\PDO::FETCH_ASSOC);
      //        $sth->execute();

      //        if (empty($row)) {
      //            return false;
      //        }

      //        while ($row = $sth->fetch()) {
      //            $instance = new $child_class();

            //  foreach ($child_class::getPersistentAttrubutes() as $attr) {
      //                $instance->$attr = $row[$attr];
      //            }

      //            $children[] = $instance;
      //        }

    //      return $children;
            
      //    } catch (\PDOException $e) {
      //        echo 'Could not connect to database: ' . $e->getMessage();
      //    }

    // }

    public function nextId() {
        // This method assumes that (1) only zero or one parent models are set, and (2) that the IDs in the database are sequential
        $id = $this->id;
        $class = get_class($this);
        $classname  = \PhpQuizzes\Base\Inflect::pluralize(strtolower(self::deNamespace($class)));

        $parent_constraint = '';
        if (isset($class::$_belongs_to)) {
            $parent_model_name  = self::modelQualify($class::$_belongs_to);
            if (!($parent_model         = $this->getParent())) {
                return false;
            }
            $parent_constraint  = "AND {$class::$_belongs_to}_id = {$parent_model->id}";
        }

        $sql = "SELECT id FROM {$classname} WHERE id > :id {$parent_constraint} ORDER BY id ASC LIMIT 1";

        try {
            $db = self::getDbHandle();
            $sth = $db->prepare($sql);
            $sth->bindParam(':id', $id);
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            $sth->execute();
            $row = $sth->fetch();

            return (empty($row) || !isset($row['id']))
                ? false
                : $row['id'];
            
        } catch (\PDOException $e) {
            echo 'Could not connect to database: ' . $e->getMessage();
        }
    }

    /**
     * Set an attribute if it is accessible
     * 
     * An attribute is accessible if it is in self::attr_accessible OR if no
     * self::attr_accessible is set.
     * 
     * First, an attempt is made to call a setter method on this class. If that is not found,
     * the attribute is set directly (if it exists). This works with both "underscore"
     * and regular attributes.
     */
    public function __set($attr, $value) {
        if ( ! isset(static::$_attr_accessible)
            || (isset(static::$_attr_accessible) && in_array($attr, self::$_attr_accessible))
        ) {
            /*
             * First attempt to use setter method. If none exists, set the attribute directly
             * Currently, this only supports single-word attributes.
             * TODO: Support "multi-word" attributes.
             */
            $setter = "set" . ucwords($attr);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } else {
                /*
                 * This will attempt to set the attribute preceeded or not by an underscore
                 * Underscore properties are for non-persistent attributes and will NOT be saved
                 * to the database.
                 */
                if (! property_exists(get_class($this), $attr)) {
                    $attr = "_{$attr}";
                }

                if (property_exists(get_class($this), $attr)) {
                    $this->$attr = $value;
                }
            }
        } else {
            // This means that accessible attributes are set, but somebody
            // tried to set an inaccessible attibute
            // Perhaps log this, since it may indicate a hacking attempt
        }
    }

    public function __get($attr) {
        if (property_exists(get_class($this), $attr)) {
            return stripcslashes($this->$attr);
        } elseif (property_exists(get_class($this), "_{$attr}")) {
            $attr = "_{$attr}";
            return stripcslashes($this->$attr);
        } else {
            $model = get_class($this);
            throw new \InvalidArgumentException("There is no attribute, '{$attr}' for model, {$model}");
        }
    }

    static public function getChildren($parent, $child = null) {
        $instances = array();
        $parent_class = get_class($parent);
        $classname = strtolower(self::deNamespace($parent_class));
        $parent_id = $parent->id;
        $child_models = array();

        // Populate $child_models[] array
        if ($child === null) {
            $child_models = $parent_class::$_has;
        } else {
            // Get children of specified type(s)
            if (is_array($child)) {
                foreach ($child as $model_candidate) {
                    if (in_array($model_candidate, $parent_class::$_has)) {
                        echo "this child, $model_candidate, is valid<br/>";
                        $child_models[] = $model_candidate;
                    }
                }
            } elseif (in_array($child, $parent_class::$_has)) {
                $child_models[] = $child;
            } else {
                throw new \Exception("No valid child models specified");
            }
        }

        try {
            $db = self::getDbHandle();

            foreach ($child_models as $modelname) {
                $modelname_db = \PhpQuizzes\Base\Inflect::pluralize($modelname);
                $sql = "SELECT * FROM {$modelname_db} WHERE {$classname}_id = {$parent_id} ORDER BY id DESC";
                $sth = $db->query($sql);
                $sth->setFetchMode(\PDO::FETCH_ASSOC);
                
                while ($row = $sth->fetch()) {
                    $class = self::modelQualify($modelname);
                    $instance = new $class();
                
                    foreach ($class::getPersistentAttrubutes() as $attr) {
                        $instance->$attr = $row[$attr];
                    }
                
                    $instances[] = $instance;
                }

            $sth = null;
            }
        } catch (\PDOException $e) {
            echo 'Could not connect to database: ' . $e->getMessage();
        }

        return $instances;
    }

    static private function getDbHandle() {
        $db = null;

        try {
            $db = new \PDO('sqlite:data/data.sq3');
            $db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        } catch (\PDOException $e) {
            echo 'Could not connect to database: ' . $e->getMessage();
        }

        return $db;
    }

    static private function modelQualify($class) {
        return "\\PhpQuizzes\Models\\" . ucwords($class);
    }

    private function hasParent() {
        return isset($class::$_belongs_to);
    }

    private static function multiFileUploads($_files) {
        $files = array();
        foreach ($_files as $modelname => $data) {
            foreach ($data as $attrib => $f_list) {
                foreach ($f_list as $number => $file) {
                    foreach ($file as $field => $value) {
                        $files[$modelname][$field][$number][$attrib] = $value;
                    }
                }
            }
        }
        return $files;
    }
}