<?php

namespace Caspian\Events;

abstract class BaseEvent implements Event
{
    /**
     *
     * Name of the event to trigger or listen
     * @var String
     *
     */
    private $name;

    /**
     *
     * Type of the event, 'EVENT' for cumulative output or 'FILTER' for overwritten output
     * @var String
     *
     */
    private $type;

    /**
     *
     * The data that need to travel with the data
     * @var Mixed
     *
     */
    private $data;

    /**
     *
     * Create a new base event (needs to be sub classed)
     *
     * @param  string the name of the event to trigger or register
     * @param  mixed  the data that need to travel with the event
     * @param  string the type of event to trigger
     * @return BaseEvent
     * @access public
     *
     */
    public function __construct($name='', $data=null, $type=Event::EVENT)
    {
        $this->name = $name;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     *
     * Get the name of the event
     *
     * @return String
     * @access public
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * Set the name of the event
     *
     * @param String the name of the event
     * @access public
     *
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     *
     * Get the type of event that will be triggered
     *
     * @return String
     * @access public
     *
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * Set the type of event that will be triggered
     *
     * @param  String the type of event : 'EVENT' or 'FILTER'
     * @access public
     *
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     *
     * Get the data that's coming with the event
     *
     * @return Mixed the data that's coming with the event
     * @access public
     *
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     *
     * Set the data that will come with the event
     *
     * @param  Mixed  the data that will come with the event
     * @access public
     *
     */
    public function setData($data)
    {
        $this->data = $data;
    }
} 