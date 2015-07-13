<?php

namespace Caspian\Events;

class BundleEvent extends BaseEvent implements Event
{
    const PRE  = 'pre.bundle';
    const POST = 'post.bundle';

    public function __construct($name=self::POST, $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 