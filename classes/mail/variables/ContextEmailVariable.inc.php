<?php

/**
 * @file classes/mail/variables/ContextEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents journal-specific email template variables
 */

namespace APP\mail\variables;

use PKP\mail\variables\ContextEmailVariable as PKPContextEmailVariable;

class ContextEmailVariable extends PKPContextEmailVariable
{
    public const CONTEXT_NAME = 'journalName';
    public const CONTEXT_URL = 'journalUrl';

    /**
     * @copydoc Variable::description()
     */
    protected static function description(): array
    {
        return array_merge(
            parent::description(),
            [
                self::CONTEXT_NAME => __('emailTemplate.variable.context.contextName'),
                self::CONTEXT_URL => __('emailTemplate.variable.context.contextUrl'),
            ]
        );
    }

    /**
     * @copydoc Variable::values()
     */
    protected function values(): array
    {
        return array_merge(
            parent::description(),
            [
                self::CONTEXT_NAME => $this->getContextName(),
                self::CONTEXT_URL => $this->getContextUrl(),
            ]
        );
    }
}
