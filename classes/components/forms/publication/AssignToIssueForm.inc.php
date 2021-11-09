<?php
/**
 * @file classes/components/form/publication/AssignToIssueForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignToIssueForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's issue.
 */

namespace APP\components\forms\publication;

use APP\facades\Repo;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FormComponent;

define('FORM_ASSIGN_TO_ISSUE', 'assignToIssue');

class AssignToIssueForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_ASSIGN_TO_ISSUE;

    /** @copydoc FormComponent::$method */
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param $action string URL to submit the form to
     * @param $publication \Publication The publication to change settings for
     * @param $publicationContext \Context The context of the publication
     */
    public function __construct($action, $publication, $publicationContext)
    {
        $this->action = $action;

        // Issue options
        $issueOptions = [['value' => '', 'label' => '']];

        $unpublishedCollector = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(false);
        $unpublishedIssues = Repo::issue()->getMany($unpublishedCollector);

        if ($unpublishedIssues->count() > 0) {
            $issueOptions[] = ['value' => '', 'label' => '--- ' . __('editor.issues.futureIssues') . ' ---'];
            foreach ($unpublishedIssues as $issue) {
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => $issue->getIssueIdentification(),
                ];
            }
        }

        $publishedCollector = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(true);
        $publishedIssues = Repo::issue()->getMany($publishedCollector);

        if ($publishedIssues->count() > 0) {
            $issueOptions[] = ['value' => '', 'label' => '--- ' . __('editor.issues.backIssues') . ' ---'];
            foreach ($publishedIssues as $issue) {
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => $issue->getIssueIdentification(),
                ];
            }
        }

        $this->addField(new FieldSelect('issueId', [
            'label' => __('issue.issue'),
            'options' => $issueOptions,
            'value' => $publication->getData('issueId') ? $publication->getData('issueId') : 0,
        ]));
    }
}
