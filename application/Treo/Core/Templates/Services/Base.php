<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Core\Templates\Services;

use Espo\ORM\Entity;

/**
 * Class Base
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Base extends \Espo\Core\Templates\Services\Base
{
    /**
     * @var string
     */
    public static $codePattern = '/^[a-z0-9_]*$/';

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // parent init
        parent::init();

        // add dependecies
        $this->addDependency('language');
    }

    /**
     * Is code valid
     *
     * @param Entity $entity
     * @param string $key
     *
     * @return bool
     */
    protected function isCodeValid(Entity $entity, string $key = 'code'): bool
    {
        // prepare result
        $result = false;

        if (!empty($entity->get($key)) && preg_match(self::$codePattern, $entity->get($key))) {
            $result = $this->isUnique($entity, $key);
        }

        return $result;
    }

    /**
     * Entity field is unique?
     *
     * @param Entity $entity
     * @param string $field
     *
     * @return bool
     */
    protected function isUnique(Entity $entity, string $field): bool
    {
        // prepare result
        $result = true;

        // find product
        $fundedEntity = $this->getEntityManager()
            ->getRepository($entity->getEntityName())
            ->select(['id'])
            ->where([$field => $entity->get($field)])
            ->findOne();

        if (!empty($fundedEntity) && $fundedEntity->get('id') != $entity->get('id')) {
            $result = false;
        }

        return $result;
    }

    /**
     * Text translate action
     *
     * @param string $label
     * @param string $category
     * @param string $scope
     * @param array|null $requiredOptions
     *
     * @return string
     */
    protected function translate(
        string $label,
        string $category = 'labels',
        string $scope = 'Global',
        array $requiredOptions = null
    ): string {
        return $this->getInjection('language')->translate($label, $category, $scope, $requiredOptions);
    }
}
