<?php

namespace CisBv\Netconf\Junos\Enum;

enum CompareFormat: string
{
    case PATCH_TEXT = 'text';
    case XML = 'xml';
    case JSON = 'json';
}
