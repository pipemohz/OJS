<?php

/**
 * @file controllers/modals/editorDecision/form/InitiateExternalReviewForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InitiateReviewForm
 * @ingroup controllers_modal_editorDecision_form
 *
 * @brief Form for creating the first review round for a submission's external
 *  review (skipping internal)
 */


use APP\workflow\EditorDecisionActionsManager;
use PKP\controllers\modals\editorDecision\form\EditorDecisionForm;

use PKP\submission\action\EditorAction;
use PKP\submission\reviewRound\ReviewRound;

class InitiateExternalReviewForm extends EditorDecisionForm
{
    /**
     * Constructor.
     *
     * @param $submission Submission
     * @param $decision int SUBMISSION_EDITOR_DECISION_...
     * @param $stageId int WORKFLOW_STAGE_ID_...
     */
    public function __construct($submission, $decision, $stageId)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        parent::__construct($submission, $decision, $stageId, 'controllers/modals/editorDecision/form/initiateExternalReviewForm.tpl');
    }

    //
    // Implement protected template methods from Form
    //
    /**
     * Execute the form.
     */
    public function execute(...$formParams)
    {
        parent::execute(...$formParams);

        $request = Application::get()->getRequest();

        // Retrieve the submission.
        $submission = $this->getSubmission();

        // Record the decision.
        $actionLabels = (new EditorDecisionActionsManager())->getActionLabels($request->getContext(), $submission, $this->getStageId(), [$this->_decision]);

        $editorAction = new EditorAction();
        $editorAction->recordDecision($request, $submission, $this->_decision, $actionLabels);

        // Move to the internal review stage.
        $editorAction->incrementWorkflowStage($submission, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);

        // Create an initial internal review round.
        $this->_initiateReviewRound($submission, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, $request, ReviewRound::REVIEW_ROUND_STATUS_PENDING_REVIEWERS);
    }
}
