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

namespace Treo\Console;

/**
 * Class DevelopMod
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class DevelopMod extends AbstractConsole
{
    /**
     * Get console command description
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return "Enable or disable development mode. 'param' can be enable|disable";
    }

    /**
     * Run action
     *
     * @param array $data
     */
    public function run(array $data): void
    {
        if ($data['param'] == 'enable') {
            $developMode = true;
        } elseif ($data['param'] == 'disable') {
            $developMode = false;
        } else {
            self::show("Param should be or 'enable', or 'disable'", self::ERROR);
        }

        if (isset($developMode)) {
            $this->getConfig()->set('developMode', $developMode);
            $this->getConfig()->save();

            // developmod for composer
            $this->composer();

            self::show("Development mode " . $data['param'] . "d", self::SUCCESS);
        }
    }


    /**
     * Developmod for composer
     */
    protected function composer(): void
    {
        // prepare data
        $data = json_decode(file_get_contents('composer.json'), true);
        if (!empty($this->getConfig()->get('developMode'))) {
            $data['minimum-stability'] = 'rc';
            $devData = ['require' => ['phpunit/phpunit' => '^7', 'squizlabs/php_codesniffer' => '*']];
        } else {
            $data['minimum-stability'] = 'stable';
            $devData = ['require' => []];
        }

        file_put_contents('composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents('data/dev-composer.json', json_encode($devData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
