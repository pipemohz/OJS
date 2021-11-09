<?php

/**
 * @file controllers/grid/issues/form/IssueGalleyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyForm
 * @ingroup issue_galley
 *
 * @see IssueGalley
 *
 * @brief Issue galley editing form.
 */

use APP\file\IssueFileManager;

use APP\template\TemplateManager;
use PKP\form\Form;

class IssueGalleyForm extends Form
{
    /** @var Issue the issue the galley belongs to */
    public $_issue = null;

    /** @var IssueGalley current galley */
    public $_issueGalley = null;

    /**
     * Constructor.
     *
     * @param $issue Issue
     * @param $issueGalley IssueGalley (optional)
     */
    public function __construct($request, $issue, $issueGalley = null)
    {
        parent::__construct('controllers/grid/issueGalleys/form/issueGalleyForm.tpl');
        $this->_issue = $issue;
        $this->_issueGalley = $issueGalley;

        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);

        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'label', 'required', 'editor.issues.galleyLabelRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'urlPath', 'optional', 'validator.alpha_dash_period', '/^[a-zA-Z0-9]+([\\.\\-_][a-zA-Z0-9]+)*$/'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));

        // Ensure a locale is provided and valid
        $journal = $request->getJournal();
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom(
            $this,
            'galleyLocale',
            'required',
            'editor.issues.galleyLocaleRequired',
            function ($galleyLocale) use ($journal) {
                return in_array($galleyLocale, $journal->getSupportedFormLocales());
            }
        ));

        if (!$issueGalley) {
            // A file must be uploaded with a newly-created issue galley.
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'temporaryFileId', 'required', 'form.fileRequired'));
        }
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $journal = $request->getJournal();
        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->assign([
            'issueId' => $this->_issue->getId(),
            'supportedLocales' => $journal->getSupportedLocaleNames(),
            'enablePublisherId' => in_array('issueGalley', (array) $request->getContext()->getData('enablePublisherId')),
        ]);
        if ($this->_issueGalley) {
            $templateMgr->assign([
                'issueGalleyId' => $this->_issueGalley->getId(),
                'issueGalley' => $this->_issueGalley,
            ]);
        }

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::validate
     */
    public function validate($callHooks = true)
    {
        // Check if public galley ID is already being used
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        $journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */

        $publicGalleyId = $this->getData('publicGalleyId');
        if ($publicGalleyId) {
            if (ctype_digit((string) $publicGalleyId)) {
                $this->addError('publicGalleyId', __('editor.publicIdentificationNumericNotAllowed', ['publicIdentifier' => $publicGalleyId]));
                $this->addErrorField('publicGalleyId');
            } elseif ($journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicGalleyId, ASSOC_TYPE_ISSUE_GALLEY, $this->_issueGalley ? $this->_issueGalley->getId() : null, true)) {
                $this->addError('publicGalleyId', __('editor.publicIdentificationExistsForTheSameType', ['publicIdentifier' => $publicGalleyId]));
                $this->addErrorField('publicGalleyId');
            }
        }

        if ($this->getData('urlPath')) {
            if (ctype_digit((string) $this->getData('urlPath'))) {
                $this->addError('urlPath', __('publication.urlPath.numberInvalid'));
                $this->addErrorField('urlPath');
            } else {
                $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
                $issueGalley = $issueGalleyDao->getByBestId($this->getData('urlPath'), $this->_issue->getId());
                if ($issueGalley &&
                    (!$this->_issueGalley || $this->_issueGalley->getId() !== $issueGalley->getId())
                ) {
                    $this->addError('urlPath', __('publication.urlPath.duplicate'));
                    $this->addErrorField('urlPath');
                }
            }
        }

        return parent::validate($callHooks);
    }

    /**
     * Initialize form data from current galley (if applicable).
     */
    public function initData()
    {
        if ($this->_issueGalley) {
            $this->_data = [
                'label' => $this->_issueGalley->getLabel(),
                'publicGalleyId' => $this->_issueGalley->getStoredPubId('publisher-id'),
                'galleyLocale' => $this->_issueGalley->getLocale(),
                'urlPath' => $this->_issueGalley->getData('urlPath'),
            ];
        } else {
            $this->_data = [];
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(
            [
                'label',
                'publicGalleyId',
                'galleyLocale',
                'temporaryFileId',
                'urlPath',
            ]
        );
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $issueFileManager = new IssueFileManager($this->_issue->getId());

        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        $user = $request->getUser();

        $issueGalley = $this->_issueGalley;
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */

        // If a temporary file ID was specified (i.e. an upload occurred), get the file for later.
        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /* @var $temporaryFileDao TemporaryFileDAO */
        $temporaryFile = $temporaryFileDao->getTemporaryFile($this->getData('temporaryFileId'), $user->getId());

        parent::execute(...$functionArgs);

        if ($issueGalley) {
            // Update an existing galley
            if ($temporaryFile) {
                // Galley has a file, delete it before uploading new one
                if ($issueGalley->getFileId()) {
                    $issueFileManager->deleteById($issueGalley->getFileId());
                }
                // Upload new file
                $issueFile = $issueFileManager->fromTemporaryFile($temporaryFile);
                $issueGalley->setFileId($issueFile->getId());
            }

            $issueGalley->setLabel($this->getData('label'));
            $issueGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
            $issueGalley->setLocale($this->getData('galleyLocale'));
            $issueGalley->setData('urlPath', $this->getData('urlPath'));

            // Update galley in the db
            $issueGalleyDao->updateObject($issueGalley);
        } else {
            // Create a new galley
            $issueGalleyFile = $issueFileManager->fromTemporaryFile($temporaryFile);

            $issueGalley = $issueGalleyDao->newDataObject();
            $issueGalley->setIssueId($this->_issue->getId());
            $issueGalley->setFileId($issueGalleyFile->getId());
            $issueGalley->setData('urlPath', $this->getData('urlPath'));

            if ($this->getData('label') == null) {
                // Generate initial label based on file type
                if (isset($fileType)) {
                    if (strstr($fileType, 'pdf')) {
                        $issueGalley->setLabel('PDF');
                    } elseif (strstr($fileType, 'postscript')) {
                        $issueGalley->setLabel('PostScript');
                    } elseif (strstr($fileType, 'xml')) {
                        $issueGalley->setLabel('XML');
                    }
                }

                if ($issueGalley->getLabel() == null) {
                    $issueGalley->setLabel(__('common.untitled'));
                }
            } else {
                $issueGalley->setLabel($this->getData('label'));
            }
            $issueGalley->setLocale($this->getData('galleyLocale'));

            $issueGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));

            // Insert new galley into the db
            $issueGalleyDao->insertObject($issueGalley);
            $this->_issueGalley = $issueGalley;
        }

        return $this->_issueGalley->getId();
    }
}
