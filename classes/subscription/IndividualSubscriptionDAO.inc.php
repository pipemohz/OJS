<?php

/**
 * @file classes/subscription/IndividualSubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionDAO
 * @ingroup subscription
 *
 * @see IndividualSubscription
 *
 * @brief Operations for retrieving and modifying IndividualSubscription objects.
 */

namespace APP\subscription;

use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\plugins\HookRegistry;

class IndividualSubscriptionDAO extends SubscriptionDAO
{
    /**
     * Retrieve an individual subscription by subscription ID.
     *
     * @param $subscriptionId int Subscription ID
     * @param $journalId int Optional journal ID
     *
     * @return IndividualSubscription
     */
    public function getById($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT	s.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 0
				AND s.subscription_id = ?
				' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = (array) $result->current();
        return $row ? $this->_fromRow($row) : null;
    }

    /**
     * Retrieve individual subscription by user ID for journal.
     *
     * @param $userId int
     * @param $journalId int
     *
     * @return IndividualSubscription
     */
    public function getByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = (array) $result->current();
        return $row ? $this->_fromRow($row) : null;
    }

    /**
     * Retrieve individual subscriptions by user ID.
     *
     * @param $userId int
     * @param $rangeInfo DBResultRange
     *
     * @return object DAOResultFactory containing IndividualSubscriptions
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?',
            [(int) $userId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Return number of individual subscriptions with given status for journal.
     *
     * @param $journalId int
     * @param $status int
     *
     * @return int
     */
    public function getStatusCount($journalId, $status = null)
    {
        $params = [(int) $journalId];
        if ($status !== null) {
            $params[] = (int) $status;
        }

        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	subscriptions s,
				subscription_types st
			WHERE	s.type_id = st.type_id AND
				st.institutional = 0 AND
				s.journal_id = ?
			' . ($status !== null ? ' AND s.status = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $row->row_count : 0;
    }

    /**
     * Get the number of individual subscriptions for a particular journal.
     *
     * @param $journalId int
     *
     * @return int
     */
    public function getSubscribedUserCount($journalId)
    {
        return $this->getStatusCount($journalId);
    }

    /**
     * Check if an individual subscription exists for a given subscriptionId.
     *
     * @param $subscriptionId int
     *
     * @return boolean
     */
    public function subscriptionExists($subscriptionId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?',
            [(int) $subscriptionId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an individual subscription exists for a given user.
     *
     * @param $subscriptionId int
     * @param $userId int
     *
     * @return boolean
     */
    public function subscriptionExistsByUser($subscriptionId, $userId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.subscription_id = ?
			AND s.user_id = ?',
            [(int) $subscriptionId, (int) $userId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an individual subscription exists for a given user and journal.
     *
     * @param $userId int
     * @param $journalId int
     *
     * @return boolean
     */
    public function subscriptionExistsByUserForJournal($userId, $journalId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM
			subscriptions s,
			subscription_types st
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = ?
			AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Generator function to create object.
     *
     * @return IndividualSubscription
     */
    public function newDataObject()
    {
        return new IndividualSubscription();
    }

    /**
     * Internal function to return an IndividualSubscription object from a row.
     *
     * @param $row array
     *
     * @return IndividualSubscription
     */
    public function _fromRow($row)
    {
        $individualSubscription = parent::_fromRow($row);
        HookRegistry::call('IndividualSubscriptionDAO::_fromRow', [&$individualSubscription, &$row]);

        return $individualSubscription;
    }

    /**
     * Insert a new individual subscription.
     *
     * @param $individualSubscription IndividualSubscription
     *
     * @return int
     */
    public function insertObject($individualSubscription)
    {
        return $this->_insertObject($individualSubscription);
    }

    /**
     * Update an existing individual subscription.
     *
     * @param $individualSubscription IndividualSubscription
     */
    public function updateObject($individualSubscription)
    {
        $this->_updateObject($individualSubscription);
    }

    /**
     * Delete an individual subscription by subscription ID.
     *
     * @param $subscriptionId int
     * @param $journalId int
     */
    public function deleteById($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $this->update('DELETE FROM subscriptions WHERE subscription_id = ?' . ($journalId ? ' AND journal_id = ?' : ''), $params);
    }

    /**
     * Delete individual subscriptions by journal ID.
     *
     * @param $journalId int
     *
     * @return boolean
     */
    public function deleteByJournalId($journalId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE journal_id = ?', [(int) $journalId]);

        $returner = true;
        foreach ($result as $row) {
            $returner = $this->deleteById($row->subscription_id);
            if (!$returner) {
                break;
            }
        }
        return $returner;
    }

    /**
     * Delete individual subscriptions by user ID.
     *
     * @param $userId int
     *
     * @return boolean
     */
    public function deleteByUserId($userId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE user_id = ?', [(int) $userId]);

        $returner = true;
        foreach ($result as $row) {
            $returner = $this->deleteById($row->subscription_id);
            if (!$returner) {
                break;
            }
        }
        return $returner;
    }

    /**
     * Delete individual subscription by user ID and journal ID.
     *
     * @param $userId int
     * @param $journalId int
     *
     * @return boolean
     */
    public function deleteByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE user_id = ? AND journal_id = ?', [(int) $userId, (int) $journalId]);

        $returner = true;
        foreach ($result as $row) {
            $returner = $this->deleteById($row->subscription_id);
            if (!$returner) {
                break;
            }
        }
        return $returner;
    }

    /**
     * Delete all individual subscriptions by subscription type ID.
     *
     * @param $subscriptionTypeId int
     *
     * @return boolean
     */
    public function deleteByTypeId($subscriptionTypeId)
    {
        $result = $this->retrieve('SELECT subscription_id FROM subscriptions WHERE type_id = ?', [(int) $subscriptionTypeId]);

        $returner = true;
        foreach ($result as $row) {
            $returner = $this->deleteById($row->subscription_id);
            if (!$returner) {
                break;
            }
        }

        return $returner;
    }

    /**
     * Retrieve all individual subscriptions.
     *
     * @param $rangeInfo DBResultRange
     *
     * @return object DAOResultFactory containing IndividualSubscriptions
     */
    public function getAll($rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT s.*
			FROM
			subscriptions s,
			subscription_types st,
			users u
			WHERE s.type_id = st.type_id
			AND st.institutional = 0
			AND s.user_id = u.user_id
			ORDER BY u.user_id, s.subscription_id',
            [],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve individual subscriptions matching a particular journal ID.
     *
     * @param $journalId int
     * @param $status int
     * @param $searchField int
     * @param $searchMatch string "is" or "contains" or "startsWith"
     * @param $search String to look in $searchField for
     * @param $dateField int
     * @param $dateFrom String date to search from
     * @param $dateTo String date to search to
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching IndividualSubscriptions
     */
    public function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null)
    {
        $params = [(int) $journalId];
        $result = $this->retrieveRange(
            $sql = 'SELECT	s.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN users u ON (s.user_id = u.user_id)
			WHERE	st.institutional = 0
				AND s.journal_id = ? ' .
            parent::_generateSearchSQL($status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params) . ' ' .
            'ORDER BY u.user_id, s.subscription_id',
            $params,
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted in subscription grid paging
    }

    /**
     * Check whether user with ID has a valid individual subscription for a given journal.
     *
     * @param $userId int
     * @param $journalId int
     * @param $check int Check using either start date, end date, or both (default)
     * @param $checkDate date (YYYY-MM-DD) Use this date instead of current date
     *
     * @return boolean
     */
    public function isValidIndividualSubscription($userId, $journalId, $check = Subscription::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        if (empty($userId) || empty($journalId)) {
            return false;
        }

        $today = $this->dateToDB(Core::getCurrentDate());

        if ($checkDate == null) {
            $checkDate = $today;
        } else {
            $checkDate = $this->dateToDB($checkDate);
        }

        switch ($check) {
            case Subscription::SUBSCRIPTION_DATE_START:
                $dateSql = sprintf('%s >= s.date_start AND %s >= s.date_start', $checkDate, $today);
                break;
            case Subscription::SUBSCRIPTION_DATE_END:
                $dateSql = sprintf('%s <= s.date_end AND %s >= s.date_start', $checkDate, $today);
                break;
            default:
                $dateSql = sprintf('%s >= s.date_start AND %s <= s.date_end', $checkDate, $checkDate);
        }

        $result = $this->retrieve(
            '
			SELECT	s.subscription_id AS subscription_id
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	s.user_id = ?
				AND s.journal_id = ?
				AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 0
				AND ((st.non_expiring = 1) OR (st.non_expiring = 0 AND (' . $dateSql . ')))
				AND (st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
				OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->subscription_id : false;
    }

    /**
     * Retrieve active individual subscriptions matching a particular end date and journal ID.
     *
     * @param $dateEnd string (YYYY-MM-DD)
     * @param $journalId int
     * @param $rangeInfo DBResultRange
     *
     * @return object DAOResultFactory containing matching IndividualSubscriptions
     */
    public function getByDateEnd($dateEnd, $journalId, $rangeInfo = null)
    {
        $dateEnd = explode('-', $dateEnd);

        $result = $this->retrieveRange(
            'SELECT	s.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN users u ON (u.user_id = s.user_id)
			WHERE	s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 0
				AND EXTRACT(YEAR FROM s.date_end) = ?
				AND EXTRACT(MONTH FROM s.date_end) = ?
				AND EXTRACT(DAY FROM s.date_end) = ?
				AND s.journal_id = ?
			ORDER BY u.user_id, s.subscription_id',
            [$dateEnd[0], $dateEnd[1], $dateEnd[2], (int) $journalId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Renew an individual subscription by dateEnd + duration of subscription type
     * if the individual subscription is expired, renew to current date + duration
     *
     * @param $individualSubscription IndividualSubscription
     *
     * @return boolean
     */
    public function renewSubscription($individualSubscription)
    {
        return $this->_renewSubscription($individualSubscription);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\IndividualSubscriptionDAO', '\IndividualSubscriptionDAO');
}
