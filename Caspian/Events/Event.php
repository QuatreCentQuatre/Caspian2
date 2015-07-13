<?php

namespace Caspian\Events;

interface Event
{
    const EVENT  = 'event';
    const FILTER = 'filter';

    public function getName();
    public function setName($name);

    public function getType();
    public function setType($type);

    public function getData();
    public function setData($data);

} 