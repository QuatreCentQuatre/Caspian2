<?php

namespace Caspian;

use Caspian\Events\Event;

class EventDispatcher extends Base
{
    /* Regitered events object */
    private static $registered_events;

    /* Triggered events history */
    private static $triggered_events = [];

    /**
     *
     * Construct
     *
     */
    public function __construct()
    {
        parent::__construct();

        if (empty(self::$registered_events)) {
            self::$registered_events = new \stdClass;
        }
    }

    /**
     *
     * Trigger an event
     *
     * @param    Event $event the event to trigger
     * @return   array callback returned values
     * @access   public
     *
     */
    public function trigger(Event $event)
    {
        $name = str_replace(" ", "-", strtolower($event->getName()));
        $out  = [];
        $data = $event->getData();

        if (!empty(self::$registered_events->{$name})) {
            foreach (self::$registered_events->{$name} as $subscriber) {
                if (!empty($subscriber->context)) {
                    if (is_object($subscriber->context)) {
                        if ($event->getType() == Event::EVENT) {
                            if (is_callable($subscriber->method)) {
                                $out[] = call_user_func($subscriber->method, $event);
                            } else {
                                $out[] = call_user_func(array($subscriber->context, $subscriber->method), $event);
                            }
                        } else {
                            if (is_callable($subscriber->method)) {
                                $data = call_user_func($subscriber->method, $event);
                            } else {
                                $data = call_user_func(array($subscriber->context, $subscriber->method), $event);
                            }
                        }
                    } else {
                        $class = new $subscriber->context;

                        if ($event->getType() == Event::EVENT) {
                            if (is_callable($subscriber->method)) {
                                $out[] = call_user_func($subscriber->method, $event);
                            } else {
                                $out[] = call_user_func(array($class, $subscriber->method), $event);
                            }
                        } else {
                            if (is_callable($subscriber->method)) {
                                $data = call_user_func($subscriber->method, $event);
                            } else {
                                $data = call_user_func(array($class, $subscriber->method), $event);
                            }
                        }
                    }
                } else {
                    if ($event->getType() == Event::EVENT) {
                        $out[] = call_user_func($subscriber->method, $event);
                    } else {
                        $data = call_user_func($subscriber->method, $event);
                    }
                }
            }
        }

        self::$triggered_events[] = $name;

        if ($event->getType() == Event::EVENT) {
            return $out;
        } else {
            return $data;
        }
    }

    /**
     *
     * Register for an event
     *
     * @param     string   event name
     * @param     mixed    the context in which for the callback (ether classname string or object)
     * @param     mixed    callback name or closure
     * @return    void
     * @access    public
     *
     */
    public function register($name, $context=null, $method)
    {
        $name = str_replace(" ", "_", strtolower($name));

        if (empty(self::$registered_events)) {
            self::$registered_events = new \stdClass;
        }

        if (empty(self::$registered_events->{$name})) {
            self::$registered_events->{$name} = array();
        }

        $event_object          = new \stdClass;
        $event_object->context = $context;
        $event_object->method  = $method;

        array_push(self::$registered_events->{$name}, $event_object);
    }

    /**
     *
     * Check if a given event has been triggered already
     *
     * @param   string  event name
     * @return  bool    yes/no
     * @access  public
     *
     */
    public function isTriggered($name)
    {
        if (in_array($name, self::$triggered_events)) {
            return true;
        } else {
            return false;
        }
    }
}
