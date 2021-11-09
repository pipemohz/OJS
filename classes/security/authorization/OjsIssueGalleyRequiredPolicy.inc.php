<?php
/**
 * @file classes/security/authorization/OjsIssueGalleyRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueGalleyRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue galley.
 */

namespace APP\security\authorization;

use PKP\security\authorization\DataObjectRequiredPolicy;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\db\DAORegistry;

use APP\issue\IssueGalley;

class OjsIssueGalleyRequiredPolicy extends DataObjectRequiredPolicy
{
    /**
     * Constructor
     *
     * @param $request PKPRequest
     * @param $args array request parameters
     * @param $operations array
     */
    public function __construct($request, &$args, $operations = null)
    {
        parent::__construct($request, $args, 'issueGalleyId', 'user.authorization.invalidIssueGalley', $operations);
    }

    //
    // Implement template methods from AuthorizationPolicy
    //
    /**
     * @see DataObjectRequiredPolicy::dataObjectEffect()
     */
    public function dataObjectEffect()
    {
        $issueGalleyId = (int)$this->getDataObjectId();
        if (!$issueGalleyId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Make sure the issue galley belongs to the journal.
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
        $issueGalley = $issueGalleyDao->getById($issueGalleyId, $issue->getId());
        if (!$issueGalley instanceof IssueGalley) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Save the publication format to the authorization context.
        $this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY, $issueGalley);
        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\security\authorization\OjsIssueGalleyRequiredPolicy', '\OjsIssueGalleyRequiredPolicy');
}
