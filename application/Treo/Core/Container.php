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

namespace Treo\Core;

use Treo\Core\Utils\File\Manager as FileManager;
use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\Config;
use Treo\Core\EventManager\Manager as EventManager;
use Treo\Core\ModuleManager\Manager as ModuleManager;

/**
 * Class Container
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Container extends \Espo\Core\Container
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    /**
     * Load
     *
     * @param $name
     *
     * @return null
     */
    protected function load($name)
    {
        // prepare load method
        $loadMethod = 'load' . ucfirst($name);

        if (in_array($loadMethod, $this->getContainerLoaders())) {
            $obj = $this->$loadMethod();
            $this->data[$name] = $obj;
        } else {
            try {
                $className = $this->get('metadata')->get('app.loaders.' . ucfirst($name));
            } catch (\Exception $e) {
            }

            if (!isset($className) || !class_exists($className)) {
                $className = '\Espo\Custom\Core\Loaders\\' . ucfirst($name);

                if (!class_exists($className)) {
                    $className = '\Treo\Core\Loaders\\' . ucfirst($name);
                }

                if (!class_exists($className)) {
                    $className = '\Espo\Core\Loaders\\' . ucfirst($name);
                }
            }

            if (class_exists($className)) {
                $loadClass = new $className($this);
                $this->data[$name] = $loadClass->load();
            }
        }

        return null;
    }

    /**
     * Reload object
     *
     * @param string $name
     *
     * @return Container
     */
    public function reload(string $name): Container
    {
        // unset
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }

        // load
        $this->load($name);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function loadInternalAclManager()
    {
        return parent::loadInternalAclManager();
    }

    /**
     * @inheritdoc
     */
    protected function loadConfig()
    {
        return new Config(new FileManager());
    }

    /**
     * @inheritdoc
     */
    protected function loadMetadata()
    {
        return new Metadata(
            $this->get('fileManager'),
            $this->get('moduleManager'),
            $this->get('eventManager'),
            $this->get('config')->get('useCache')
        );
    }

    /**
     * @inheritdoc
     */
    protected function loadLog()
    {
        return parent::loadLog();
    }

    /**
     * @inheritdoc
     */
    protected function loadFileManager()
    {
        return parent::loadFileManager();
    }

    /**
     * Load module manager
     *
     * @return ModuleManager
     */
    protected function loadModuleManager(): ModuleManager
    {
        return new ModuleManager($this);
    }

    /**
     * Load EventManager
     *
     * @return EventManager
     */
    protected function loadEventManager(): EventManager
    {
        return (new EventManager())->setContainer($this);
    }

    /**
     * Get container loaders
     *
     * @param string $name
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function getContainerLoaders(string $name = self::class): array
    {
        // prepare result
        $result = [];

        $container = new \ReflectionClass($name);
        foreach ($container->getMethods() as $m) {
            if ($m->class == $name && preg_match("/^load(.*)$/", $m->name) && $m->name != 'load') {
                $result[] = $m->name;
            }
        }

        return $result;
    }
}
