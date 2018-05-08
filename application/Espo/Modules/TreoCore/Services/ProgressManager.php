<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Utils\Json;
use Espo\Modules\TreoCore\Services\StatusActionInterface;
use Slim\Http\Request;

/**
 * ProgressManager service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManager extends AbstractProgressManager
{

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        /**
         * Add dependencies
         */
        $this->addDependency('language');
        $this->addDependency('metadata');
        $this->addDependency('serviceFactory');
        $this->addDependency('progressManager');
        $this->addDependency('eventManager');
        $this->addDependency('websocket');
    }

    /**
     * @var int
     */
    public static $maxSize = 15;

    /**
     * Is need to show progress popup
     *
     * @return bool
     */
    public function isShowPopup(): bool
    {
        // prepare params
        $userId = $this->getUser()->get('id');
        $status = self::$progressStatus['new'];

        // prepare sql
        $sql
            = "SELECT 
                  COUNT(id) as `total_count`
                FROM
                  progress_manager
                WHERE deleted = 0 AND status='{$status}' AND created_by_id='{$userId}'";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($result['total_count']);
    }

    /**
     * Get data for progresses popup
     *
     * @param Request $request
     *
     * @return array
     */
    public function popupData(Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // prepare request data
        $maxSize = (!empty($request->get('maxSize'))) ? (int)$request->get('maxSize') : self::$maxSize;

        if (!empty($data = $this->getDbData($maxSize))) {
            // prepare new records
            $newRecords = [];

            // set total
            $result['total'] = $this->getDbDataTotal();

            foreach ($data as $row) {
                // prepare status key
                $statusKey = array_flip(self::$progressStatus)[$row['status']];

                $result['list'][] = [
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'progress' => round($row['progress'], 2),
                    'status'   => [
                        'key'       => $statusKey,
                        'translate' => $this->translate('progressStatus', $statusKey)
                    ],
                    'actions'  => $this->getItemActions($statusKey, $row),
                ];

                if ($statusKey == 'new') {
                    $newRecords[] = $row['id'];
                }
            }

            /**
             * Update status for new records
             */
            if (!empty($newRecords)) {
                $this->updateStatus($newRecords, 'in_progress');
            }
        }

        return $result;
    }

    /**
     * Execute job
     *
     * @return bool
     */
    public function executeProgressJobs(): bool
    {
        // prepare result
        $result = false;

        if (!empty($records = $this->getDbData())) {
            // get config
            $config = $this->getProgressConfig();

            foreach ($records as $record) {
                if (isset($config['type'][$record['type']]['service'])) {
                    // create service
                    $service = $this->getInjection('serviceFactory')
                        ->create($config['type'][$record['type']]['service']);
                    if ($service instanceof ProgressJobInterface && $service->executeProgressJob($record)) {
                        // update record
                        $this->updateRecord($record['id'], $record['type'], $service);
                        // notify user
                        $this->notifyUser($service, $record);
                    }
                }
            }

            // prepare result
            $result = true;

            // refresh websocket
            $this->getInjection('websocket')->refresh('progress_manager');
        }

        return $result;
    }

    /**
     * Get item actions
     *
     * @param string $status
     * @param array  $record
     *
     * @return array
     */
    public function getItemActions(string $status, array $record): array
    {
        // prepare config
        $config = $this->getProgressConfig();

        // prepare data
        $data = [];

        /**
         * For status action
         */
        if (isset($config['statusAction'][$status]) && is_array($config['statusAction'][$status])) {
            $data = array_merge($data, $config['statusAction'][$status]);
        }

        /**
         * For type action
         */
        if (isset($config['type'][$record['type']]['action'][$status])) {
            $data = array_merge($data, $config['type'][$record['type']]['action'][$status]);
        }

        /**
         * Set items to result
         */
        $result = [];
        foreach ($data as $action) {
            if (isset($config['actionService'][$action])) {
                // create service
                $service = $this->getInjection('serviceFactory')->create($config['actionService'][$action]);

                if (!empty($service) && $service instanceof StatusActionInterface) {
                    $result[] = [
                        'type' => $action,
                        'data' => $service->getProgressStatusActionData($record),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Translate field
     *
     * @param string $tab
     * @param string $key
     *
     * @return string
     */
    public function translate(string $tab, string $key): string
    {
        return $this->getInjection('language')->translate($key, $tab, 'ProgressManager');
    }

    /**
     * Get DB data
     *
     * @param int $maxSize
     *
     * @return array
     */
    protected function getDbData(int $maxSize = null): array
    {
        // prepare sql
        $sql
            = "SELECT
                  id              as `id`,
                  name            as `name`,
                  deleted         as `deleted`,
                  progress        as `progress`,
                  progress_offset as `progressOffset`,
                  type            as `type`,
                  data            as `data`,
                  status          as `status`,
                  created_at      as `createdAt`,
                  created_by_id   as `createdById`
                FROM
                  progress_manager
                WHERE 
                  deleted=0 ";

        if (is_null($maxSize)) {
            // prepare statuses
            $statuses = implode("','", [self::$progressStatus['new'], self::$progressStatus['in_progress']]);

            $sql .= "AND status IN ('{$statuses}') ";
        } else {
            // prepare user id
            $userId = $this->getUser()->get('id');

            $sql .= "AND created_by_id='{$userId}' ";
        }

        $sql .= "ORDER BY status ASC, created_at DESC ";

        if (!is_null($maxSize)) {
            $sql .= "LIMIT {$maxSize} OFFSET 0";
        }

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? $data : [];
    }

    /**
     * Get DB data total
     *
     * @return int
     */
    protected function getDbDataTotal(): int
    {
        // prepare sql
        $sql
            = "SELECT
                   COUNT(id) as `total_count`
                FROM
                  progress_manager
                WHERE
                  deleted = 0";

        // execute sql
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        return (!empty($data['total_count'])) ? (int)$data['total_count'] : 0;
    }

    /**
     * Update record
     *
     * @param string               $id
     * @param string               $type
     * @param ProgressJobInterface $service
     *
     * @return bool
     */
    protected function updateRecord(string $id, string $type, ProgressJobInterface $service): bool
    {
        // prepare result
        $result = false;

        if (!empty($id)) {
            // prepare params
            $date = date('Y-m-d H:i:s');
            $status = self::$progressStatus[$service->getStatus()];
            $progress = $service->getProgress();
            $offset = $service->getOffset();
            $data = Json::encode($service->getData());
            $eventData = [
                'id'       => $id,
                'type'     => $type,
                'status'   => $status,
                'progress' => $progress,
                'data'     => $data,
            ];

            // triggered event
            $this->getInjection('eventManager')->triggered('ProgressManager', 'beforeUpdate', $eventData);

            // prepare sql
            $sql = "UPDATE progress_manager SET `status`='{$status}', `progress`={$progress}, "
                . "`progress_offset`={$offset}, `data`='{$data}', modified_at='{$date}' WHERE id='{$id}'";

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Update status
     *
     * @param array $ids
     *
     * @return void
     */
    protected function updateStatus(array $ids, string $status): void
    {
        // prepare params
        $status = self::$progressStatus[$status];
        $ids = implode("','", $ids);

        // prepare sql
        $sql = "UPDATE progress_manager SET `status`='{$status}' WHERE id IN ('{$ids}')";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();
    }

    /**
     * Notify user
     *
     * @param ProgressJobInterface $service
     * @param array                $record
     *
     * @return bool
     */
    protected function notifyUser(ProgressJobInterface $service, array $record): bool
    {
        // prepare result
        $result = false;

        if (in_array($service->getStatus(), ['success', 'error'])) {
            // prepare message
            $message = $this->translate('notificationMessages', $service->getStatus());

            // create notification
            $notification = $this->getEntityManager()->getEntity('Notification');
            $notification->set(
                [
                    'type'    => 'Message',
                    'userId'  => $record['createdById'],
                    'message' => sprintf($message, $record['name'])
                ]
            );
            $this->getEntityManager()->saveEntity($notification);

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Get progress config
     *
     * @return array
     */
    protected function getProgressConfig(): array
    {
        return $this->getInjection('progressManager')->getProgressConfig();
    }
}