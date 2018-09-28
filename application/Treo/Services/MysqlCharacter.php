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

namespace Treo\Services;

use Espo\Core\Utils\Database\Schema\Utils;

/**
 * MysqlCharacter service
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class MysqlCharacter extends \Espo\Services\MysqlCharacter
{

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $this->addDependencyList(
            [
                'entityManager',
                'ormMetadata',
                'schema',
                'config',
                'database',
                'dataManager'
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function jobConvertToMb4()
    {
        $pdo = $this->getInjection('entityManager')->getPDO();
        $ormMeta = $this->getInjection('ormMetadata')->getData(true);

        $databaseSchema = $this->getInjection('schema');
        $maxIndexLength = $databaseSchema->getMaxIndexLength();
        if ($maxIndexLength > 1000) {
            $maxIndexLength = 1000;
        }

        //Account name
        $sth = $pdo->prepare("SELECT `name` FROM `account` WHERE LENGTH(name) > 249");
        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        if (empty($row)) {
            $sth = $pdo->prepare("ALTER TABLE `account` MODIFY `name` VARCHAR(249)");
            $sth->execute();
        }

        $fieldListExceededIndexMaxLength = Utils::getFieldListExceededIndexMaxLength($ormMeta, $maxIndexLength);

        foreach ($ormMeta as $entityName => $entityParams) {

            $tableName = \Espo\Core\Utils\Util::toUnderScore($entityName);

            //Get table columns params
            $query = "SHOW FULL COLUMNS FROM `" . $tableName . "` WHERE `Collation` <> 'utf8mb4_unicode_ci'";

            try {
                $sth = $pdo->prepare($query);
                $sth->execute();
            } catch (\Exception $e) {
                $GLOBALS['log']->debug('Utf8mb4: Table does not exist - ' . $e->getMessage());
                continue;
            }

            $columnParams = array();
            $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rowList as $row) {
                $columnParams[$row['Field']] = $row;
            }
            //END: get table columns params
            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

                $columnName = \Espo\Core\Utils\Util::toUnderScore($fieldName);

                if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                    continue;
                }

                if (isset($fieldListExceededIndexMaxLength[$entityName])
                    && in_array($fieldName, $fieldListExceededIndexMaxLength[$entityName])) {
                    continue;
                }

                if (!isset($columnParams[$columnName]) || empty($columnParams[$columnName]['Type'])) {
                    continue;
                }

                $query = null;

                switch ($fieldParams['type']) {
                    case 'varchar':
                    case 'text':
                    case 'jsonObject':
                    case 'jsonArray':
                        $query
                            = "ALTER TABLE `$tableName`
                            CHANGE COLUMN `$columnName` `$columnName` " . $columnParams[$columnName]['Type'] . "
                            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                        ";
                        break;
                }

                if (!empty($query)) {
                    $GLOBALS['log']->debug('Utf8mb4: execute the query - [' . $query . '].');

                    try {
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    } catch (\Exception $e) {
                        $message = "Utf8mb4: FAILED executing the query - [$query], details: ";
                        $message .= $e->getMessage() . '.';

                        $GLOBALS['log']->warning($message);
                    }
                }
            }
        }

        $config = $this->getInjection('config');
        $database = $this->getInjection('database');
        if (!isset($database['charset']) || $database['charset'] != 'utf8mb4') {
            $database['charset'] = 'utf8mb4';
            $config->set('database', $database);
            $config->save();
        }

        $this->getInjection('dataManager')->rebuild();
    }
}