<?php

/**
 * @file pages/search/SearchHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 * @ingroup pages_search
 *
 * @brief Handle site index requests.
 */

import('classes.search.ArticleSearch');

use APP\facades\Repo;

use APP\handler\Handler;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\template\TemplateManager;

class SearchHandler extends Handler
{
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if ($request->getContext()) {
            $this->addPolicy(new OjsJournalMustPublishPolicy($request));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Show the search form
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function index($args, $request)
    {
        $this->validate(null, $request);
        $this->search($args, $request);
    }

    /**
     * Private function to transmit current filter values
     * to the template.
     *
     * @param $request PKPRequest
     * @param $templateMgr TemplateManager
     * @param $searchFilters array
     */
    public function _assignSearchFilters($request, &$templateMgr, $searchFilters)
    {
        // Get the journal id (if any).
        $journal = & $searchFilters['searchJournal'];
        $journalId = ($journal ? $journal->getId() : null);
        $searchFilters['searchJournal'] = $journalId;

        // Assign all filters except for dates which need special treatment.
        $templateSearchFilters = [];
        foreach ($searchFilters as $filterName => $filterValue) {
            if (in_array($filterName, ['fromDate', 'toDate'])) {
                continue;
            }
            $templateSearchFilters[$filterName] = $filterValue;
        }

        // Assign the filters to the template.
        $templateMgr->assign($templateSearchFilters);

        // Special case: publication date filters.
        foreach (['From', 'To'] as $fromTo) {
            $month = $request->getUserVar("date${fromTo}Month");
            $day = $request->getUserVar("date${fromTo}Day");
            $year = $request->getUserVar("date${fromTo}Year");
            if (empty($year)) {
                $date = null;
                $hasEmptyFilters = true;
            } else {
                $defaultMonth = ($fromTo == 'From' ? 1 : 12);
                $defaultDay = ($fromTo == 'From' ? 1 : 31);
                $date = date(
                    'Y-m-d H:i:s',
                    mktime(
                        0,
                        0,
                        0,
                        empty($month) ? $defaultMonth : $month,
                        empty($day) ? $defaultDay : $day,
                        $year
                    )
                );
                $hasActiveFilters = true;
            }
            $templateMgr->assign([
                "date${fromTo}Month" => $month,
                "date${fromTo}Day" => $day,
                "date${fromTo}Year" => $year,
                "date${fromTo}" => $date
            ]);
        }

        // Assign the year range.
        $collector = Repo::publication()->getCollector();
        if ($journalId) {
            $collector->filterByContextIds([(int) $journalId]);
        }
        $yearRange = Repo::publication()->getDateBoundaries($collector);
        $yearStart = substr($yearRange->min_date_published, 0, 4);
        $yearEnd = substr($yearRange->max_date_published, 0, 4);
        $templateMgr->assign([
            'yearStart' => $yearStart,
            'yearEnd' => $yearEnd,
        ]);
    }

    /**
     * Show the search form
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function search($args, $request)
    {
        $this->validate(null, $request);

        // Get and transform active filters.
        $articleSearch = new ArticleSearch();
        $searchFilters = $articleSearch->getSearchFilters($request);
        $keywords = $articleSearch->getKeywordsFromSearchFilters($searchFilters);

        // Get the range info.
        $rangeInfo = $this->getRangeInfo($request, 'search');

        // Retrieve results.
        $error = '';
        $results = $articleSearch->retrieveResults(
            $request,
            $searchFilters['searchJournal'],
            $keywords,
            $error,
            $searchFilters['fromDate'],
            $searchFilters['toDate'],
            $rangeInfo
        );

        // Prepare and display the search template.
        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setCacheability(TemplateManager::CACHEABILITY_NO_STORE);

        // Result set ordering options.
        $orderByOptions = $articleSearch->getResultSetOrderingOptions($request);
        $templateMgr->assign('searchResultOrderOptions', $orderByOptions);
        $orderDirOptions = $articleSearch->getResultSetOrderingDirectionOptions();
        $templateMgr->assign('searchResultOrderDirOptions', $orderDirOptions);

        // Result set ordering selection.
        [$orderBy, $orderDir] = $articleSearch->getResultSetOrdering($request);
        $templateMgr->assign('orderBy', $orderBy);
        $templateMgr->assign('orderDir', $orderDir);

        // Similar documents.
        $templateMgr->assign('simDocsEnabled', true);

        // Result set display.
        $this->_assignSearchFilters($request, $templateMgr, $searchFilters);
        $templateMgr->assign('results', $results);
        $templateMgr->assign('error', $error);
        $templateMgr->display('frontend/pages/search.tpl');
    }

    /**
     * Redirect to a search query that shows documents
     * similar to the one identified by an article id in the
     * request.
     *
     * @param $args array
     * @param $request Request
     */
    public function similarDocuments($args, &$request)
    {
        $this->validate(null, $request);

        // Retrieve the (mandatory) ID of the article that
        // we want similar documents for.
        $articleId = $request->getUserVar('articleId');
        if (!is_numeric($articleId)) {
            $request->redirect(null, 'search');
        }

        // Check whether a search plugin provides terms for a similarity search.
        $articleSearch = new ArticleSearch();
        $searchTerms = $articleSearch->getSimilarityTerms($articleId);

        // Redirect to a search query with the identified search terms (if any).
        if (empty($searchTerms)) {
            $searchParams = null;
        } else {
            $searchParams = ['query' => implode(' ', $searchTerms)];
        }
        $request->redirect(null, 'search', null, null, $searchParams);
    }

    /**
     * Setup common template variables.
     *
     * @param $request PKPRequest
     */
    public function setupTemplate($request)
    {
        parent::setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $journal = $request->getJournal();
        if (!$journal || !$journal->getData('restrictSiteAccess')) {
            $templateMgr->setCacheability(TemplateManager::CACHEABILITY_PUBLIC);
        }
    }
}
