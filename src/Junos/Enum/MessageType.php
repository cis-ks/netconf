<?php

namespace CisBv\Netconf\Junos\Enum;

enum MessageType
{
    case UNDEFINED;
    case COMPARE_MESSAGE_JSON;
    case COMPARE_MESSAGE_XML;
    case COMPARE_MESSAGE_TEXT;
    case EDIT_CONFIG;
}
