<?php

namespace m50\GitCalendar\Contracts;

use m50\GitCalendar\GitData;

interface GitApi
{
    public function getEventCountsByDay(): GitData;
}