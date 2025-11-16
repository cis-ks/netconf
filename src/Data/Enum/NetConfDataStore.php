<?php

namespace CisBv\Netconf\Data\Enum;

enum NetConfDataStore: string
{
    case RUNNING = 'running';
    case STARTUP = 'startup';
    case CANDIDATE = 'candidate';
}
