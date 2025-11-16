<?php

namespace CisBv\Netconf\Junos\Enum;

enum CompareType: string
{
    case ROLLBACK = 'rollback';
    case REVISION = 'revision-id';
}
