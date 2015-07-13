<?php

namespace Caspian\Events;

class RoutingEvent extends BaseEvent implements Event
{
    const PRE  = 'pre.routing';
    const POST = 'post.routing';

    public function __construct($name=self::POST, $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 