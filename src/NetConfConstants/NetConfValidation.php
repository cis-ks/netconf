<?php

namespace CisBv\Netconf\NetConfConstants;

trait NetConfValidation
{
    public const array EDIT_CONFIG_OPERATIONS = ['merge', 'replace', 'create', 'delete', 'remove'];
    public const array EDIT_CONFIG_DEFAULT_OPERATIONS = ['merge', 'replace', 'none'];
    public const array EDIT_CONFIG_TEST_OPTIONS = ['test-then-set', 'set', 'test-only'];

    public const array EDIT_CONFIG_ERROR_OPTIONS = ['stop-on-error', 'continue-on-error', 'rollback-on-error'];
}
