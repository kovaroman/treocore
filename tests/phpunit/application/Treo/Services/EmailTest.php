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

namespace Treo\Services;

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class EmailTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class EmailTest extends TestCase
{
    /**
     * Test is sendTestEmail method return true
     */
    public function testIsSendTestEmailReturnTrue()
    {
        $service = $this->createMockService(Email::class, ['sendEmail']);
        $service
            ->expects($this->any())
            ->method('sendEmail')
            ->willReturn(null);

        $this->assertTrue($service->sendTestEmail(
            [
                'emailAddress' => 'email'
            ]
        ));

        $this->assertTrue($service->sendTestEmail(
            [
                'emailAddress' => 'email',
                'server' => 'server',
                'security' => 'ssl'
            ]
        ));

        $this->assertTrue($service->sendTestEmail(
            [
                'emailAddress' => 'email',
                'port' => '1111'
            ]
        ));
    }
}
