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

declare(strict_types = 1);

namespace Espo\Modules\TreoCore\Core\SelectManagers;

use Espo\Core\SelectManagers\Base as EspoBase;
use Espo\Modules\TreoCore\Core\Utils\EventManager;

/**
 * Class Base
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Base extends EspoBase
{

    /**
     * Where
     *
     * @param $where
     * @param $result
     */
    protected function where($where, &$result)
    {
        $listener = $this->getEntityType() . 'SelectManager';

        $where = $this->getEventManager()->triggered($listener, 'modifyWhere', $where);

        parent::where($where, $result);
    }

    /**
     * Get event manager
     *
     * @return EventManager
     */
    protected function getEventManager(): EventManager
    {
        return $this->getEntityManager()->getContainer()->get('eventManager');
    }
}