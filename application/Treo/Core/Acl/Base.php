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

namespace Treo\Core\Acl;

use Espo\Core\Acl\Base as EspoBase;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * Class Base
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Base extends EspoBase
{
    /**
     * Init
     */
    protected function init()
    {
        $this->addDependency('metadata');
    }

    /**
     * Check is Owner param
     *
     * @param User   $user
     * @param Entity $entity
     *
     * @return bool
     */
    public function checkIsOwner(User $user, Entity $entity)
    {
        // prepare data
        $hasOwnerUser = $this
            ->getInjection('metadata')
            ->get('scopes.' . $entity->getEntityType() . '.hasOwnerUser');
        $hasAssignedUser = $this
            ->getInjection('metadata')
            ->get('scopes.' . $entity->getEntityType() . '.hasAssignedUser');

        if ($hasOwnerUser) {
            if ($entity->has('ownerUserId')) {
                if ($user->id === $entity->get('ownerUserId')) {
                    return true;
                }
            }
        }

        if ($hasAssignedUser) {
            if ($entity->has('assignedUserId')) {
                if ($user->id === $entity->get('assignedUserId')) {
                    return true;
                }
            }
        }

        if ($entity->hasAttribute('createdById') && !$hasOwnerUser && !$hasAssignedUser) {
            if ($entity->has('createdById')) {
                if ($user->id === $entity->get('createdById')) {
                    return true;
                }
            }
        }

        if ($entity->hasAttribute('assignedUsersIds') && $entity->hasRelation('assignedUsers')) {
            if ($entity->hasLinkMultipleId('assignedUsers', $user->id)) {
                return true;
            }
        }

        return false;
    }
}