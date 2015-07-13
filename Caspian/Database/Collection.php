<?php

namespace Caspian\Database;

use Caspian\Application;
use Caspian\Configuration;
use Caspian\Events\DatabaseEvent;
use Caspian\Events\Event;
use Caspian\Utils\Inflector;
use Caspian\Utils\IO;

class Collection
{
    private static $connection;
    private $collection;
    private $isGridFS = false;
    private $query = array('select' => array(), 'distinct' => array(),  'where' => array(), 'order' => array(), 'limit' => '', 'offset' => '', 'paginate' => '');

    /**
     *
     * __construct
     *
     * Create mongo connection and setup basic configuration for it
     *
     * @param  string $collection the collection to connect to
     * @param  bool   $isGridFS if the collection need gridFS specification
     * @access public
     * @final
     *
     */
    public final function __construct($collection = null, $isGridFS = false)
    {
        $config = Configuration::get('database', Application::$_environment);

        if (empty(self::$connection)) {
            if (!empty($config->user) && !empty($config->password)) {
                self::$connection = new \MongoClient("mongodb://{$config->user}:{$config->password}@{$config->host}:{$config->port}/{$config->database}");
            } else {
                self::$connection = new \MongoClient("mongodb://{$config->host}:{$config->port}/{$config->database}");
            }
        }

        if (empty($collection)) {
            $inflector  = new Inflector;
            $collection = $inflector->underscore($inflector->pluralize(get_class($this)));
        } else {
            $inflector  = new Inflector;
            $collection = $inflector->underscore($inflector->pluralize($collection));
        }

        if ($isGridFS == true) {
            $this->collection = self::$connection->{$config->database}->getGridFS($collection);
            $this->isGridFS   = true;
        } else {
            $this->collection = self::$connection->{$config->database}->{$collection};
        }
    }

    /**
     *
     * set
     *
     * Set the collection manually
     *
     * @param   string      collection name
     * @access  public
     *
     */
    public final function set($collection)
    {
        $config = Configuration::get('database', Application::$_environment);

        $inflector        = new Inflector;
        $collection       = $inflector->underscore($inflector->pluralize($collection));

        if ($this->isGridFS == true) {
            $this->collection = self::$connection->{$config->dataase}->getGridFS($collection);
        } else {
            $this->collection = self::$connection->{$config->database}->{$collection};
        }
    }

    /**
     *
     * Get a collection instance from the given collection name
     *
     * @param   string              collection name
     * @return  \MongoCollection    collection object
     * @access  private
     * @static
     * @final
     *
     */
    private static final function getCollectionInstance($collection)
    {
        $config = Configuration::get('database', Application::$_environment);

        $inflector  = new Inflector;
        $collection = $inflector->underscore($inflector->pluralize($collection));
        return self::$connection->{$config->database}->{$collection};
    }

    /**
     *
     * Set the select clause
     *
     * @param   string  the select clause
     * @return  object  this object
     * @access  public
     * @throws  \RuntimeException
     * @final
     *
     */
    public final function select($select)
    {
        if (is_string($select)) {
            $fields = explode(",", $select);
            foreach ($fields as $num => $value) {
                $fields[trim($value)] = true;
            }

            $this->query['select'] = $fields;
            return $this;
        } else {
            $type = gettype($select);
            throw new \RuntimeException("select expects a string to be passed. received {$type}");
        }
    }

    /**
     *
     * Set the distinct clause
     *
     * @param   string  the distinct clause
     * @return  object  this object
     * @access  public
     * @throws  \RuntimeException
     * @final
     *
     */
    public final function distinct($field)
    {
        $this->query['distinct'] = $field;
        return $this;
    }

    /**
     *
     * Set the where clause
     *
     * @param   array   the where clause
     * @return  object  this object
     * @access  public
     * @final
     *
     */
    public final function where($where)
    {
        foreach ($where as $key => $value) {
            if ($key == '_id') {
                if (is_string($value)) {
                    $where[$key] = new \MongoId($value);
                }
            }
        }

        $this->query['where'] = $where;
        return $this;
    }

    /**
     *
     * Set the order clause
     *
     * @param   string  the order clause
     * @return  object  this object
     * @access  public
     * @final
     *
     */
    public final function order($order)
    {
        if (is_string($order)) {
            list($field, $sort) = explode(" ", $order);
            if (strtoupper($sort) == 'ASC') {
                $this->query['order'] = array($field => 1);
            } elseif (strtoupper($sort) == 'DESC') {
                $this->query['order'] = array($field => -1);
            }
        } else {
            $this->query['order'] = $order;
        }

        return $this;
    }

    /**
     *
     * Set the limit clause
     *
     * @param   int     the limit clause
     * @return  object  this object
     * @access  public
     * @final
     *
     */
    public final function limit($limit)
    {
       $this->query['limit'] = $limit;
       return $this;
    }

    /**
     *
     * Set the offset clause
     *
     * @param   int     the offset clause
     * @return  object  this object
     * @access  public
     * @final
     *
     */
    public final function offset($offset)
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    /**
     *
     * Get a paginated result object on this query
     *
     * @param   int     page number
     * @param   int     max per page
     * @return  object  this object
     * @access  public
     * @final
     *
     */
    public final function paginate($page, $max)
    {
        $offset = $page * $max - $max;
        $this->offset($offset)->limit($max);
        $this->query['paginate'] = true;
        return $this;
    }

    /**
     *
     * Find a single document
     *
     * @return  mixed   Model class with data set, null if no document is found
     * @access  public
     * @final
     *
     */
    public final function find()
    {
        $result = $this->collection->findOne($this->query['where'], $this->query['select']);

        if (!empty($result)) {
            $class    = get_class($this);
            $instance = new $class($this->collection->getName());

            foreach ($result as $key => $value) {
                if (is_array($value)) {
                    $instance->{$key} = [];

                    foreach ($value as $k => $v) {
                        $instance->{$key}[$k] = $v;
                    }
                } elseif (is_object($value) && get_class($value) != 'MongoId') {
                    $instance->{$key} = new \stdClass;

                    foreach ($value as $k => $v) {
                        $instance->{$key}->{$k} = $v;
                    }
                } else {
                    $instance->{$key} = $value;
                }
            }

            return $instance;
        } else {
            return null;
        }
    }

    /**
     *
     * Find all documents that matches query
     *
     * @return  mixed   Array of documents, null if no documents is found
     * @access  public
     * @final
     *
     */
    public final function findAll()
    {
        if(!empty($this->query['distinct'])) {
            return $this->findDistinctValues();
        } else {
            $request = $this->collection->find($this->query['where'], $this->query['select']);

            /* Optional query elements */
            if (!empty($this->query['order'])) { $request = $request->sort($this->query['order']); }
            if (!empty($this->query['offset'])) { $request = $request->skip($this->query['offset']); }
            if (!empty($this->query['limit'])) { $request = $request->limit($this->query['limit']); }
        }

        $count = $request->count();

        if ($count == 0) {
            if ($this->query['paginate']) {
                $pagination                = new \stdClass;
                $pagination->current_page  = 1;
                $pagination->count         = 0;
                $pagination->next_page     = null;
                $pagination->previous_page = null;
                $pagination->total_results = 0;

                $out             = new \stdClass;
                $out->results    = [];
                $out->pagination = $pagination;
                return $out;
            } else {
                return array();
            }
        } else {
            $class   = get_class($this);
            $results = array();

            foreach ($request as $doc) {
                $instance = new $class($this->collection->getName());

                foreach ($doc as $key => $value) {
                    if (is_array($value)) {
                        $instance->{$key} = [];

                        foreach ($value as $k => $v) {
                            $instance->{$key}[$k] = $v;
                        }
                    } elseif (is_object($value) && get_class($value) != 'MongoId') {
                        $instance->{$key} = new \stdClass;

                        foreach ($value as $k => $v) {
                            $instance->{$key}->{$k} = $v;
                        }
                    } else {
                        $instance->{$key} = $value;
                    }
                }

                $results[] = $instance;
            }

            /* If we paginate */
            if ($this->query['paginate']) {
                $request = $this->collection->find($this->query['where'], $this->query['select']);

                $total_rows  = $request->count();
                $total_pages = ceil($total_rows / $this->query['limit']);
                $page        = ($this->query['offset'] + $this->query['limit']) / $this->query['limit'];

                if ($this->query['offset'] == 0) {
                    $page = 1;
                }

                $current_page  = floor($page);
                $next_page     = ($page < $total_pages) ? $page + 1 : '';
                $previous_page = ($page - 1 > 0) ? $page - 1 : '';

                $pagination                = new \stdClass;
                $pagination->current_page  = $current_page;
                $pagination->count         = $total_pages;
                $pagination->next_page     = $next_page;
                $pagination->previous_page = $previous_page;
                $pagination->total_results = $total_rows;

                $out             = new \stdClass;
                $out->results    = $results;
                $out->pagination = $pagination;

                return $out;
            } else {
                return $results;
            }
        }
    }

    /**
     *
     * Execute distinct query
     *
     * @return  mixed   Array of documents, null if no documents is found
     * @access  public
     * @final
     *
     */
    public final function findDistinctValues()
    {

        $results = $this->collection->distinct($this->query['distinct'], $this->query['where']);

        if(!empty($this->query['order']) && $this->query['order'][$this->query['distinct']] == '-1') {
            rsort($results);
        } else {
            sort($results);
        }

        $count = count($results);

        if ($this->query['paginate']) {
            $results = array_chunk($results, $this->query['limit']);
        }

        if ($count == 0) {
            if ($this->query['paginate']) {
                $pagination                = new \stdClass;
                $pagination->current_page  = 1;
                $pagination->count         = 0;
                $pagination->next_page     = null;
                $pagination->previous_page = null;

                $out             = new \stdClass;
                $out->results    = [];
                $out->pagination = $pagination;
                return $out;
            } else {
                return array();
            }
        } else {
            if ($this->query['paginate']) {
                $total_pages = ceil($count / $this->query['limit']);
                $page        = ($this->query['offset'] + $this->query['limit']) / $this->query['limit'];

                if ($this->query['offset'] == 0) {
                    $page = 1;
                }

                $current_page  = floor($page);
                $next_page     = ($page < $total_pages) ? $page + 1 : '';
                $previous_page = ($page - 1 > 0) ? $page - 1 : '';

                $pagination                = new \stdClass;
                $pagination->current_page  = $current_page;
                $pagination->count         = $total_pages;
                $pagination->next_page     = $next_page;
                $pagination->previous_page = $previous_page;

                $out             = new \stdClass;
                $out->results    = $results[$page - 1];
                $out->pagination = $pagination;

                return $out;
            } else {
                return $results;
            }
        }
    }

    /**
     *
     * Find a document by it's id
     *
     * @param   mixed   id
     * @return  mixed   Model object if we have a it, null otherwise
     * @access  public
     * @final
     *
     */
    public final function findById($id)
    {
        return $this->where(array('_id' => new \MongoId($id)))->find();
    }

    /**
     *
     * A static version of findById
     *
     * @param   mixed   id
     * @return  mixed   Model object if we have a it, null otherwise
     * @access  public
     * @static
     * @final
     *
     */
    public static final function withId($id)
    {
        $class    = get_called_class();
        $instance = new $class;

        return $instance->where(array('_id' => new \MongoId($id)))->find();
    }

    /**
     *
     * Run the aggregate method with the give array of data
     * (project, match, order, limit, etc.)
     *
     * @param   array   array of arguments
     * @return  array   the aggregate result
     * @access  public
     * @final
     *
     */
    public final function aggregate($aggregate)
    {
        return $this->collection->aggregate($aggregate);
    }

    /**
     *
     * Count how many documents are in the collection
     *
     * @return  int     the count
     * @access  public
     * @final
     *
     */
    public final function count($by=null)
    {
        return $this->collection->count($by);
    }

    /**
     *
     * Destroy the record or destroy records based on a query
     *
     * @param   bool    destroy all that match?
     * @access  public
     * @final
     *
     */
    public final function destroy($all=false)
    {
        $app = Application::$instance;

        $this->collection->remove($this->query['where'], array('justOne' => $all));

        /* EVENT : DatabaseEvent POST_DELETE */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::POST_DELETE, $this->query['where']));
    }

    /**
     *
     * Destroy a record by it's id
     *
     * @param   mixed   string or mongoId object
     * @access  public
     * @final
     *
     */
    public final function destroyById($id)
    {
        $app = Application::$instance;

        if (is_string($id)) {
            $id = new \MongoId($id);
        }

        $this->collection->remove(array('_id' => $id));

        /* EVENT : DatabaseEvent POST_DELETE */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::POST_DELETE, (string)$id));
    }

    /**
     *
     * Drop the current collection
     *
     * @access  public
     * @final
     *
     */
    public final function drop()
    {
        $app = Application::$instance;

        $this->collection->drop();

        /* EVENT : DatabaseEvent COLLECTION_DROP */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::COLLECTION_DROP, $this->collection->getName()));
    }

    /**
     *
     * Insert or update a record
     *
     * @param   string      the update key to look for
     * @return  \MongoId    the mongo id that is created or updated
     * @access  public
     * @finaal
     *
     */
    public final function save($key='_id', $force = false)
    {
        $app = Application::$instance;

        if (empty($this->{$key}) || $force == true) {
            /* Add */
            foreach ($this as $var => $value) {
                if ($var != 'connection' && $var != 'query' && $var != 'collection' && $var != 'isGridFS') {
                    $query[$var] = $value;
                }
            }

            $query['creation_date']     = new \MongoDate(time());
            $query['modification_date'] = new \MongoDate(time());

            $this->collection->insert($query);

            $clone      = $this;
            $clone->_id = $query['_id'];

            /* EVENT : DatabaseEvent POST_INSERT */
            $app->events->trigger(new DatabaseEvent(DatabaseEvent::POST_INSERT, $clone, Event::FILTER));

            return $query['_id'];
        } else {
            /* Update */
            foreach ($this as $var => $value) {
                if ($var != 'connection' && $var != 'query' && $var != 'collection' && $var != 'isGridFS' && $var != $key) {
                    $query[$var] = $value;
                }
            }

            unset($query['creation_date']);
            $query['modification_date'] = new \MongoDate(time());

            if(!empty($query['publication_date'])) {
                $query['publication_date'] = new \MongoDate($query['publication_date']->sec);
            }

            if(!empty($query['publication_end_date'])) {
                $query['publication_end_date'] = new \MongoDate($query['publication_end_date']->sec);
            }

            $where = array($key => $this->{$key});
            $this->collection->update($where, array('$set' => $query));

            /* EVENT : DatabaseEvent POST_UPDATE */
            $app->events->trigger(new DatabaseEvent(DatabaseEvent::POST_UPDATE, $this));

            return $this->_id;
        }
    }

    /**
     *
     * Drop column(s) from given collection
     *
     * @param   mixed   $columns - string if one column, array if many
     * @access  public
     * @final
     *
     */
    public final function dropColumns($collection, $columns)
    {
        $where = array('content_type' => $collection);
        $query = array();

        if(is_array($columns)) {
            foreach($columns as $column) {
                $query[$column] = '';
            }
        } else {
            $query[$columns] = '';
        }

        $this->collection->update($where, array('$unset' => $query));
    }

    /**
     *
     * create an index on given field
     *
     * @param   string  collection name
     * @param   string  the name of the field
     * @param   int     the direction (1 or -1)
     * @param   array   list of options
     * @access  public
     * @static
     * @final
     *
     */
    public static final function createIndex($collection, $field, $sorting=-1, $options=array())
    {
        self::getCollectionInstance($collection)->ensureIndex(array($field => $sorting), $options);
    }

    /**
     *
     * create a compound index on given fields
     *
     * @param   string  collection name
     * @param   array   list of fields with their directions
     * @param   array   list of options
     * @access  public
     * @static
     * @final
     *
     */
    public static final function createCompoundIndex($collection, $list, $options=array())
    {
        self::getCollectionInstance($collection)->ensureIndex($list, $options);
    }

    /**
     *
     * Delete the given field's index (if exists)
     *
     * @param   string  collection name
     * @param   mixed   string for simple index, array for compound index
     * @access  public
     * @static
     * @final
     *
     */
    public static final function deleteIndex($collection, $field)
    {
        self::getCollectionInstance($collection)->deleteIndex($field);
    }

    /**
     *
     * Delete all indexes from the collection
     *
     * @param   string  collection name
     * @access  public
     * @static
     * @final
     *
     */
    public static final function deleteIndexes($collection)
    {
        self::getCollectionInstance($collection)->deleteIndexes();
    }

    /**
     *
     * Create a db reference for the given document id
     *
     * @param   mixed   mongoId object or id string
     * @return  mixed   document object if is valid, null otherwise
     * @access  public
     * @static
     * @final
     *
     */
    public static final function reference($id)
    {
        if (is_string($id)) {
            $id = new \MongoId($id);
        }

        $collection = self::getCollectionInstance(get_called_class());
        $document   = $collection->findOne(array('_id' => $id));
        return $collection->createDBRef($document);
    }

    /**
     *
     * Get the document from the reference
     *
     * @param   mixed   array or object of a reference
     * @return  object  the referenced object
     * @access  public
     * @static
     * @final
     *
     */
    public static final function getReference($element)
    {
        $collection = self::getCollectionInstance(get_called_class());

        $doc      = $collection->getDBRef((array)$element);
        $class    = get_called_class();
        $instance = new $class;

        /* Set up values */
        if (!empty($doc)) {
            foreach ($doc as $key => $value) {
                if (is_array($value)) {
                    $instance->{$key} = [];

                    foreach ($value as $k => $v) {
                        $instance->{$key}[$k] = $v;
                    }
                } elseif (is_object($value) && get_class($value) != 'MongoId') {
                    $instance->{$key} = new \stdClass;

                    foreach ($value as $k => $v) {
                        $instance->{$key}->{$k} = $v;
                    }
                } else {
                    $instance->{$key} = $value;
                }
            }
        }

        return $instance;
    }

    /**
     *
     * Store a file in mongodb's GridFS
     *
     * Metadata is saved the same way a regular model works ($model->my_meta = ...)
     *
     * @param   mixed   the file path, the binary data or the upload filename
     * @param   string  the file hash in database
     * @param   bool    is it a file path
     * @param   bool    is it an upload (needs to match with file to work (ex: true and true))
     * @return  string  the file id
     * @access  public
     * @final
     *
     */
    public final function addFile($file, $hash, $is_file=true, $is_upload=false)
    {
        $query = array();
        $query['hash'] = $hash;
        $query['type'] = IO::getExtension($file);

        foreach ($this as $var => $value) {
            if ($var != 'connection' && $var != 'query' && $var != 'collection' && $var != 'isGridFS') {
                $query[$var] = $value;
            }
        }

        if ($is_file) {
            if ($is_upload) {
                $id = $this->collection->storeUpload($file, $query);
            } else {
                $id = $this->collection->storeFile($file, $query);
            }
        } else {
            $id = $this->collection->storeBytes($file, $query);
        }

        $app = Application::$instance;

        /* EVENT : DatabaseEvent GRID_POST_INSERT */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::GRID_POST_INSERT, $id));

        return $id;
    }

    /**
     *
     * Get a file by querying for it
     *
     * @return  \MongoGridFSFile|null    the gridfs object or null
     * @access  public
     * @final
     *
     */
    public final function getFile()
    {
        $result = $this->collection->findOne($this->query['where'], array());

        if (!empty($result)) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     *
     * Convenience method for getFile (get by it's id)
     *
     * @param   mixed                   string or mongoId object
     * @return  \MongoGridFSFile|null   the gridfs object or null
     * @access  public
     * @final
     *
     */
    public final function getFileById($id)
    {
        if (is_string($id)) {
            $id = new \MongoId($id);
        }

        return $this->where(array('_id' => $id))->getFile();
    }

    /**
     *
     * Delete a file by it's id
     *
     * @param   mixed   string or mongoId object
     * @access  public
     * @final
     *
     */
    public final function destroyFileById($id)
    {
        if (is_string($id)) {
            $id = new \MongoId($id);
        }

        $app = Application::$instance;

        $this->collection->delete($id);

        /* EVENT : DatabaseEvent GRID_POST_DELETE */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::GRID_POST_DELETE, (string)$id));
    }

    /**
     *
     * Delete a/many file(s) by a query
     *
     * @param   bool    delete just 1 file matching the query
     * @access  public
     * @final
     *
     */
    public final function destroyFiles($just_one=true)
    {
        $app = Application::$instance;

        $this->collection->remove($this->query['where'], array('justOne' => $just_one));

        /* EVENT : DatabaseEvent GRID_POST_DELETE */
        $app->events->trigger(new DatabaseEvent(DatabaseEvent::GRID_POST_DELETE, $this->query['where']));
    }

    /**
     *
     * Disconnect from server
     *
     * @access  public
     * @static
     * @final
     *
     */
    public static final function disconnect()
    {
        if (self::$connection) {
            self::$connection->close(true);
        }
    }
}
