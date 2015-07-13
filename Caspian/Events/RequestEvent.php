<?php

namespace Caspian\Events;

class RequestEvent extends BaseEvent implements Event
{
    const PRE  = 'pre.request';
    const POST = 'post.request';

    public function __construct($name=self::POST, $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 