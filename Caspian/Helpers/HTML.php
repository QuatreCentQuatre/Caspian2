<?php

namespace Caspian\Helpers;

use Caspian\Base;
use Caspian\Configuration;

class HTML extends Base
{
    /**
     *
     * Load css files (each argument is 1 file) (outputs the css html code directly)
     *
     * @param     string     infinite list of files (ex: css('file1', 'file2', 'file3'))
     * @return    void
     * @access    protected
     * @final
     *
     */
    public final function css()
    {
        $files = func_get_args();
        $path  = str_replace($this->app->site_url . '/', '', 'public/app') . '/css';

        foreach ($files as $file) {
            if (!strstr($file, '.css')) {
                if (!stristr($file, '.htc')) {
                    /* Add .css if not added (lazy loading) */
                    $file .= '.css';
                }
            }

            if (file_exists($this->app->root_path . '/' . $path . '/' . $file)) {
                /* Add file modification time only for developement and staging environments (helps with debugging) */
                if ($this->app->config->development->anticaching == Configuration::YES) {
                    $time = filemtime($path . '/' . $file);
                    echo '<link rel="stylesheet" href="' . $this->app->site_url . '/' . $path . '/' . $file . '?' . $time . '">' . "\n";
                } else {
                    echo '<link rel="stylesheet" href="' . $this->app->site_url . '/' . $path . '/' . $file . '">' . "\n";
                }
            }
        }
    }

    /**
     *
     * Load stylesheets from specified bundle path
     *
     * @param   string  bundle name as first argument
     * @param   mixed   indefinite list of css to load
     * @return  void
     * @access  public
     * @final
     *
     */
    public final function bundlecss()
    {
        $args   = func_get_args();
        $bundle = $args[0];
        unset($args[0]);

        $path  = 'public/' . $bundle . '/css';

        foreach ($args as $file) {
            if (!strstr($file, '.css')) {
                if (!stristr($file, '.htc')) {
                    /* Add .css if not added (lazy loading) */
                    $file .= '.css';
                }
            }

            if ($this->app->config->development->anticaching == Configuration::YES) {
                /* Add file modification time only for developement and staging environments (helps with debugging) */
                if (\Caspian\Configuration::get('configuration', 'development.anticaching') == Configuration::YES) {
                    $time = filemtime($path . '/' . $file);
                    echo '<link rel="stylesheet" href="' . $this->app->site_url . '/' . $path . '/' . $file . '?' . $time . '">' . "\n";
                } else {
                    echo '<link rel="stylesheet" href="' . $this->app->site_url . '/' . $path . '/' . $file . '">' . "\n";
                }
            }
        }
    }

    /**
     *
     * Load javascript files (each argument is 1 file) (outputs the javascript html code directly)
     *
     * @param     string     infinite list of files (ex: javascript('file1', 'file2', 'file3'))
     * @return    void
     * @access    protected
     * @final
     *
     */
    public final function js()
    {
        $files = func_get_args();
        $path  = str_replace($this->app->site_url . '/', '', 'public/app') . '/javascript';

        foreach ($files as $file) {
            if (!strstr($file, '.js')) {
                /* Add .js if not added (lazy loading) */
                $file .= '.js';
            }

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path . '/' . $file)) {
                /* Add file modification time only for developement and staging environments (helps with debugging) */
                if ($this->app->config->development->anticaching == Configuration::YES) {
                    $time = filemtime($path . '/' . $file);
                    echo '<script type="text/javascript" src="' . $this->app->site_url . '/' . $path . '/' . $file . '?' . $time . '"></script>' . "\n";
                } else {
                    echo '<script type="text/javascript" src="' . $this->app->site_url . '/' . $path . '/' . $file . '"></script>' . "\n";
                }
            }
        }
    }

    /**
     *
     * Load javascripts from specified bundle path
     *
     * @param   string  bundle name as first argument
     * @param   mixed   indefinite list of javascripts to load
     * @return  void
     * @access  public
     * @final
     *
     */
    public final function bundlejs()
    {
        $args   = func_get_args();
        $bundle = $args[0];
        unset($args[0]);

        $path  = 'public/' . $bundle . '/javascript';

        foreach ($args as $file) {
            if (!strstr($file, '.js')) {
                /* Add .js if not added (lazy loading) */
                $file .= '.js';
            }

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path . '/' . $file)) {
                /* Add file modification time only for developement and staging environments (helps with debugging) */
                if ($this->app->config->development->anticaching == Configuration::YES) {
                    $time = filemtime($path . '/' . $file);
                    echo '<script type="text/javascript" src="' . $this->app->site_url . '/' . $path . '/' . $file . '?' . $time . '"></script>' . "\n";
                } else {
                    echo '<script type="text/javascript" src="' . $this->app->site_url . '/' . $path . '/' . $file . '"></script>' . "\n";
                }
            }
        }
    }

    /**
     *
     * Load an image from the views public images path (outputs directly)
     *
     * @param     string     image source
     * @return    void
     * @access    protected
     * @final
     *
     */
    public final function image($src)
    {
        echo $this->app->site_url . '/public/app/images/' . $src;
    }
}
