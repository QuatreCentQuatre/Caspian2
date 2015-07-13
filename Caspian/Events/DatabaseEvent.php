<?php

namespace Caspian\Events;

class DatabaseEvent extends BaseEvent implements Event
{
    const POST_DELETE      = 'db.delete';
    const POST_INSERT      = 'db.insert';
    const POST_UPDATE      = 'db.update';
    const COLLECTION_DROP  = 'db.collection.drop';
    const GRID_POST_INSERT = 'db.grid.insert';
    const GRID_POST_DELETE = 'db.grid.delete';

    public function __construct($name='', $content=null, $type=Event::EVENT)
    {
        parent::__construct($name, $content, $type);
    }

} 