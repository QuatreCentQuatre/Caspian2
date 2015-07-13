<?php

namespace Caspian;

use Caspian\Database\Collection;

class Upload extends Base
{
    /* Type */
    const FILES  = 'files';
    const IMAGES = 'images';
    const CACHE  = 'cache';

    /* Extensions */
    const WILDCARD = '*';

    private static $files;
    private static $cache;

    public function __construct()
    {
        $config = Configuration::get('configuration', 'uploading');
        $driver = ucfirst(strtolower($config->driver));

        include_once dirname(__FILE__) . '/Upload/' . $driver . '.php';
        $class        = '\\Caspian\\Upload\\' . $driver;
        $this->driver = new $class;
    }

    /**
     *
     * Handle uploading of a file
     *
     * @param   array   the $_FILES array to upload ($_FILES[the_file_name])
     * @param   string  the type of file (files, images)
     * @param   string  the extensions to accept
     * @param   string  the filename we want for the uploaded image - if null uniqid
     * @return  string  the file hash
     * @access  public
     *
     */
    public function handleUpload($file, $type=self::FILES, $extension=self::WILDCARD, $name=null)
    {
        if ($file['error'] == 0) {
            $pos = strrpos($file['name'], '.');

            if ($extension != self::WILDCARD) {
                if (stristr($extension, ',')) {
                    $extensions = explode(",", $extension);
                } else {
                    $extensions = array($extension);
                }

                $pos = strrpos($file['name'], '.');
                $ext = strtolower(substr($file['name'], $pos + 1));

                if (!in_array($ext, $extensions)) {
                    return null;
                }
            }

            /* Unique name */
            $ext      = strtolower(substr($file['name'], $pos + 1));
            $filename = !empty($name) ? $name : uniqid() . '.' . $ext;

            $file   = $this->driver->moveObject($file['tmp_name'], $type, $filename);
            $return = $this->writeFilesystem($type, $file);
            return $return;
        } else {
            return null;
        }
    }

    /**
     *
     * Alias for handleUpload
     *
     */
    public function uploadFile($file)
    {
        return $this->handleUpload($file);
    }

    /**
     *
     * Alias for handleUpload
     *
     * @param   string  the file to upload
     * @param   string  the filename of the destination file
     * @param   string  the folder where we want to upload
     * @return  string  filename
     */
    public function uploadImage($file, $filename=null, $folder=self::IMAGES)
    {
        return $this->handleUpload($file, $folder, 'jpg,jpeg,png,gif', $filename);
    }

    /**
     *
     * Upload a local file to the CDN Cache system
     *
     * @param   string  the file to upload
     * @param   string  hash
     * @return  string  the new file's path
     * @access  public
     *
     */
    public function addCache($file, $hash)
    {
        $name   = basename($file);
        $file   = $this->driver->moveObject($file, self::CACHE, $name);
        $return = $this->writeCacheFilesystem($hash, $file);
        return $return;
    }

    /**
     *
     * Get a cached element url
     *
     * @param   string  element hash
     * @return  string  element url
     * @access  public
     *
     */
    public function getCache($hash)
    {
        if (!empty(self::$cache[$hash])) {
            return self::$cache[$hash];
        } else {
            return null;
        }
    }

    /**
     *
     * Check if given hash is already cached
     *
     * @param   string  the hash
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isCached($hash)
    {
        if (!empty(self::$cache[$hash])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * Delete one or many files
     *
     * @param   mixed   string for 1 file, array for many
     * @access  public
     *
     */
    public function deleteFile($hash)
    {
        if (is_array($hash)) {
            $names = [];

            foreach ($hash as $hash_string) {
                $names[] = $this->searchFileSystem($hash_string, 'name');
                $this->deleteFromFilesystem($hash_string);
            }

            $this->driver->deleteObject(self::FILES, $names);
        } else {
            $name = $this->searchFileSystem($hash, 'name');
            $this->driver->deleteObject(self::FILES, array($name));
            $this->deleteFromFilesystem($hash);
        }
    }

    /**
     *
     * Delete one or many files
     *
     * @param   mixed   string for 1 file, array for many
     * @access  public
     *
     */
    public function deleteImage($hash)
    {
        if (is_array($hash)) {
            $names = [];

            foreach ($hash as $hash_string) {
                $names[] = $this->searchFileSystem($hash_string, 'name');

                $this->deleteFromFilesystem($hash_string);
                $this->deleteFromCache($hash_string);
            }

            $this->driver->deleteObject(self::IMAGES, $names);
        } else {
            $name = $this->searchFileSystem($hash, 'name');
            $this->driver->deleteObject(self::IMAGES, $name);
            $this->deleteFromFilesystem($hash);

            $cache_files = $this->searchCacheFiles($hash);
            foreach ($cache_files as $the_hash => $file) {
                $this->deleteFromCache($the_hash);
                $name = basename($file);
                echo $name . ' -- ';

                $this->driver->deleteObject(self::CACHE, $name);
            }
        }
    }

    /**
     *
     * Get the requested file
     *
     * @param   string  the hash of the file
     * @param   string  what to return (path or name)
     * @return  string  the path to the file
     * @access  public
     *
     */
    public function get($hash, $type='path')
    {
        return $this->searchFileSystem($hash, $type);
    }

    /**
     *
     * Test the loaded driver for upload
     *
     * @param   string  full path to file
     * @param   string  name to use after upload
     * @return  bool    success / failure
     * @access  public
     *
     */
    public function testDriverUpload($file, $name)
    {
        $file = $this->driver->moveObject($file, self::FILES, $name);
        $return = $this->writeFilesystem(self::FILES, $file);
        echo "Created file with " . $return . ' as key';
    }

    /**
     *
     * Test the driver's Get URL capability
     *
     * @param   string  file name
     * @return  string  the file's url
     * @access  public
     *
     */
    public function testDriverGet($name)
    {
        return $this->driver->getObjectURL(self::FILES, $name);
    }

    /**
     *
     * Add a file to the filesystem
     *
     * @param   string  the type of file
     * @param   string  files public url
     * @return  string  the hash for the file (to be saved by application)
     * @access  private
     *
     */
    private function writeFileSystem($type, $filepath)
    {
        /* Fix for weird bug on specific php/apache setups */
        $hash   = 'f' . md5(uniqid());
        $name   = basename($filepath);
        $found  = $this->searchFileSystem($hash);

        if (empty($found)) {
            $model       = new Collection('uploaded_files');
            $model->hash = $hash;
            $model->path = $filepath;
            $model->name = $name;
            $model->save();

            return $hash;
        } else {
            return $hash;
        }
    }

    /**
     *
     * Add a file to the cache filesystem
     *
     * @param   string  the hash
     * @param   string  files public url
     * @return  string  the hash for the file (to be saved by application)
     * @access  private
     *
     */
    private function writeCacheFileSystem($hash, $filepath)
    {
        $name   = basename($filepath);
        $found  = $this->getCache($hash);

        if (empty($found)) {
            $model       = new Collection('uploaded_caches');
            $model->hash = $hash;
            $model->path = $filepath;

            return $hash;
        } else {
            return $hash;
        }
    }

    /**
     *
     * Search the file system for the requested file
     *
     * @param   string  file hash
     * @param   string  the return value (path or name)
     * @return  string  the file path or null
     * @access  private
     *
     */
    private function searchFileSystem($hash, $return_value="path")
    {
        $model = new Collection('uploaded_files');

        $item = $model->where(array('hash' => $hash))->find();

        if (!empty($item)) {
            if ($return_value == 'name') {
                return $item->name;
            }

            return $item->path;
        }
    }

    /**
     *
     * Delete a file from the file system
     *
     * @param   string  file hash
     * @access  private
     *
     */
    private function deleteFromFilesystem($hash)
    {
        $model = new Collection('uploaded_files');
        $model->where(array('hash' => $hash))->destroy();
    }

    /**
     *
     * Delete a file from the cache file
     *
     * @param   string  file hash
     * @access  private
     *
     */
    private function deleteFromCache($hash)
    {
        $model = new Collection('uploaded_caches');
        $model->where(array('hash' => $hash))->destroy();
    }

    /**
     *
     * Search for files that match the hash
     *
     * @param   string  hash
     * @return  array   list of cache hits
     * @access  private
     *
     */
    private function searchCacheFiles($hash)
    {
        $model = new Collection('uploaded_caches');
        $items = $model->where(array('hash' => new \MongoRegex('/' . $hash . '/')))->findAll();
        return $items;
    }
}

/* Driver */
abstract class UploadDriver
{
    abstract public function moveObject($file, $directory, $name);
    abstract public function deleteObject($directory, $name);
    abstract public function getObject($directory, $name);
    abstract public function getObjectURL($directory, $name);
}
