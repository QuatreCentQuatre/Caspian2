<?php

namespace Caspian\Events;

class ControllerEvent extends BaseEvent implements Event
{

    const PRE  = 'pre.controller';
    const POST = 'post.controller';

    public function __construct($name=self::POST, $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 