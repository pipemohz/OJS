<?php

/**
 * @file classes/journal/Section.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Section
 * @ingroup journal
 *
 * @see SectionDAO
 *
 * @brief Describes basic section properties.
 */

namespace APP\journal;

use PKP\context\PKPSection;

class Section extends PKPSection
{
    /**
     * Get localized abbreviation of journal section.
     *
     * @return string
     */
    public function getLocalizedAbbrev()
    {
        return $this->getLocalizedData('abbrev');
    }


    //
    // Get/set methods
    //

    /**
     * Get ID of journal.
     *
     * @return int
     */
    public function getJournalId()
    {
        return $this->getContextId();
    }

    /**
     * Set ID of journal.
     *
     * @param $journalId int
     */
    public function setJournalId($journalId)
    {
        return $this->setContextId($journalId);
    }

    /**
     * Get section title abbreviation.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getAbbrev($locale)
    {
        return $this->getData('abbrev', $locale);
    }

    /**
     * Set section title abbreviation.
     *
     * @param $abbrev string
     * @param $locale string
     */
    public function setAbbrev($abbrev, $locale)
    {
        return $this->setData('abbrev', $abbrev, $locale);
    }

    /**
     * Get abstract word count limit.
     *
     * @return int
     */
    public function getAbstractWordCount()
    {
        return $this->getData('wordCount');
    }

    /**
     * Set abstract word count limit.
     *
     * @param $wordCount int
     */
    public function setAbstractWordCount($wordCount)
    {
        return $this->setData('wordCount', $wordCount);
    }

    /**
     * Get "will/will not be indexed" setting of section.
     *
     * @return boolean
     */
    public function getMetaIndexed()
    {
        return $this->getData('metaIndexed');
    }

    /**
     * Set "will/will not be indexed" setting of section.
     *
     * @param $metaIndexed boolean
     */
    public function setMetaIndexed($metaIndexed)
    {
        return $this->setData('metaIndexed', $metaIndexed);
    }

    /**
     * Get peer-reviewed setting of section.
     *
     * @return boolean
     */
    public function getMetaReviewed()
    {
        return $this->getData('metaReviewed');
    }

    /**
     * Set peer-reviewed setting of section.
     *
     * @param $metaReviewed boolean
     */
    public function setMetaReviewed($metaReviewed)
    {
        return $this->setData('metaReviewed', $metaReviewed);
    }

    /**
     * Get boolean indicating whether abstracts are required
     *
     * @return boolean
     */
    public function getAbstractsNotRequired()
    {
        return $this->getData('abstractsNotRequired');
    }

    /**
     * Set boolean indicating whether abstracts are required
     *
     * @param $abstractsNotRequired boolean
     */
    public function setAbstractsNotRequired($abstractsNotRequired)
    {
        return $this->setData('abstractsNotRequired', $abstractsNotRequired);
    }

    /**
     * Get localized string identifying type of items in this section.
     *
     * @return string
     */
    public function getLocalizedIdentifyType()
    {
        return $this->getLocalizedData('identifyType');
    }

    /**
     * Get string identifying type of items in this section.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getIdentifyType($locale)
    {
        return $this->getData('identifyType', $locale);
    }

    /**
     * Set string identifying type of items in this section.
     *
     * @param $identifyType string
     * @param $locale string
     */
    public function setIdentifyType($identifyType, $locale)
    {
        return $this->setData('identifyType', $identifyType, $locale);
    }

    /**
     * Return boolean indicating if title should be hidden in issue ToC.
     *
     * @return boolean
     */
    public function getHideTitle()
    {
        return $this->getData('hideTitle');
    }

    /**
     * Set if title should be hidden in issue ToC.
     *
     * @param $hideTitle boolean
     */
    public function setHideTitle($hideTitle)
    {
        return $this->setData('hideTitle', $hideTitle);
    }

    /**
     * Return boolean indicating if author should be hidden in issue ToC.
     *
     * @return boolean
     */
    public function getHideAuthor()
    {
        return $this->getData('hideAuthor');
    }

    /**
     * Set if author should be hidden in issue ToC.
     *
     * @param $hideAuthor boolean
     */
    public function setHideAuthor($hideAuthor)
    {
        return $this->setData('hideAuthor', $hideAuthor);
    }

    /**
     * Return boolean indicating if section should be inactivated.
     *
     * @return int
     */
    public function getIsInactive()
    {
        return $this->getData('isInactive');
    }

    /**
     * Set if section should be inactivated.
     *
     * @param $isInactive int
     */
    public function setIsInactive($isInactive)
    {
        $this->setData('isInactive', $isInactive);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\journal\Section', '\Section');
}
