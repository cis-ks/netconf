<?php

namespace CisBv\Netconf\Junos\Enum;

enum CompareDatabase: string
{
    case CANDIDATE = 'candidate';
    case COMMITTED = 'committed';
}
