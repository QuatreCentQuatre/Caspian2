<?php

namespace Caspian\Database;

use Caspian\Database\Collection;

abstract class Model extends Collection
{
    abstract public function init();
}