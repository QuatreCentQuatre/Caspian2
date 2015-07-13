<?php

namespace Caspian\Upload;

use Caspian\Database\Collection;

class Mongodb extends \Caspian\Upload
{
    private $collection;

    public function __construct()
    {
        $this->collection = new Collection('uploads', true);
    }

    /**
     *
     * Upload a file to database
     *
     * @param   string  path name to file to upload
     * @param   string  directory to use (images, files)
     * @param   string  filename to use
     * @return  mixed   string: file path on success, null on error
     * @access  public
     *
     */
    public function moveObject($file, $directory, $name)
    {
        return $this->collection->addFile($file, $name);
    }

    /**
     *
     * Get the requested object
     *
     * @param   string  $name
     * @return  object  the file from database
     * @access  public
     *
     */
    public function getObject($name)
    {
        $this->collection->where(array('name' => $name));

        return $this->collection->getFile();
    }

    /**
     *
     * Get the public url for the requested object
     *
     * @param   string  $name
     * @return  string  public url or null if object does not exist
     * @access  public
     *
     */
    public function getObjectURL($name)
    {
        $this->collection->where(array('name' => $name));

        return $this->collection->getFile();
    }

    /**
     *
     * Get the requested object
     *
     * @param   string  id of the file
     * @return  object  the file from database
     * @access  public
     *
     */
    public function getObjectById($id)
    {
        return $this->collection->getFileById($id);
    }

    /**
     *
     * Delete the given object from id
     *
     * @param   string   file id
     * @access  public
     *
     */
    public function deleteObject($id)
    {
        $this->collection->destroyFileById($id);
    }
}