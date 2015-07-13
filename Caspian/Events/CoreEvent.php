<?php

namespace Caspian\Events;

class CoreEvent extends BaseEvent implements Event
{
    const LOCALE      = 'locale';
    const PRE_RENDER  = 'pre.render';
    const RENDER      = 'render';
    const POST_RENDER = 'post.render';
    const SHUTDOWN    = 'shutdown';

    public function __construct($name='', $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 