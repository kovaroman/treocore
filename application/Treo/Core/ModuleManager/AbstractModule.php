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

namespace Treo\Core\ModuleManager;

use Espo\Core\Utils\DataUtil;
use Espo\Core\Utils\File\Unifier;
use Treo\Core\Container;
use Espo\Core\Utils\Json;
use Treo\Core\Utils\Util;
use Treo\Core\Utils\Route;
use Treo\Core\Loaders\Layout;
use Treo\Core\Loaders\HookManager;

/**
 * Class AbstractModule
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractModule
{
    /**
     * @var bool
     */
    protected $isTreoModule = true;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $package;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Unifier
     */
    protected $unifier;

    /**
     * @var Unifier
     */
    protected $objUnifier;

    /**
     * @var null
     */
    private $hookManager = null;

    /**
     * @var null
     */
    private $routeUtil = null;

    /**
     * Get module load order
     *
     * @return int
     */
    abstract public static function getLoadOrder(): int;

    /**
     * AbstractModule constructor.
     *
     * @param string    $id
     * @param string    $path
     * @param array     $package
     * @param Container $container
     */
    public function __construct(
        string $id,
        string $path,
        array $package,
        Container $container
    ) {
        $this->id = $id;
        $this->path = $path;
        $this->package = $package;
        $this->container = $container;
    }

    /**
     * Get client path
     *
     * @return string
     */
    public function getClientPath(): string
    {
        return $this->path . 'client/';
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getComposerName(): string
    {
        return (!empty($this->package['name'])) ? (string)$this->package['name'] : "-";
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if (!empty($this->package['extra']['name']['default'])) {
            return (string)$this->package['extra']['name']['default'];
        }

        return "";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if (!empty($this->package['extra']['description']['default'])) {
            return (string)$this->package['extra']['description']['default'];
        }

        return "";
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return (!empty($this->package['version'])) ? $this->package['version'] : "";
    }

    /**
     * Load module services
     *
     * @return array
     */
    public function loadServices(): array
    {
        // prepare result
        $result = [];

        // prepare path
        $path = $this->path . 'app/Services';

        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if (preg_match_all('/^(.*)\.php$/', $item, $matches)) {
                    $result[$matches[1][0]] = "\\" . $this->id . "\\Services\\" . $matches[1][0];
                }
            }
        }

        return $result;
    }

    /**
     * Load module metadata
     *
     * @param \stdClass $data
     */
    public function loadMetadata(\stdClass &$data)
    {
        $metadata = $this
            ->getObjUnifier()
            ->unify('metadata', $this->path . 'app/Resources/metadata', true);
        $data = DataUtil::merge($data, $metadata);
    }

    /**
     * Load module layouts
     *
     * @param string $scope
     * @param string $name
     * @param array  $data
     */
    public function loadLayouts(string $scope, string $name, array &$data)
    {
        // load layout class
        $layout = (new Layout($this->container))->load();

        // prepare file path
        $filePath = $layout->concatPath($this->path . 'app/Resources/layouts', $scope);
        $fileFullPath = $layout->concatPath($filePath, $name . '.json');

        if (file_exists($fileFullPath)) {
            // get file data
            $fileData = $this->container->get('fileManager')->getContents($fileFullPath);

            // prepare data
            $data = array_merge_recursive($data, Json::decode($fileData, true));
        }
    }

    /**
     * Load module routes
     *
     * @param array $data
     */
    public function loadRoutes(array &$data)
    {
        $data = $this->getRouteUtil()->getAddData($data, $this->path . 'app/Resources/routes.json');
    }

    /**
     * Load module listeners
     *
     * @param array $listeners
     */
    public function loadListeners(array &$listeners)
    {
        // prepare path
        $dirPath = $this->path . 'app/Listeners';

        if (file_exists($dirPath) && is_dir($dirPath)) {
            foreach (scandir($dirPath) as $file) {
                if (!in_array($file, ['.', '..'])) {
                    // prepare name
                    $name = str_replace(".php", "", $file);

                    // push
                    $listeners[$name][] = "\\" . $this->id . "\\Listeners\\" . $name;
                }
            }
        }
    }

    /**
     * Load module translates
     *
     * @param array $data
     */
    public function loadTranslates(array &$data)
    {
        $data = Util::merge($data, $this->getUnifier()->unify('i18n', $this->path . 'app/Resources/i18n', true));
    }

    /**
     * Load module hooks
     *
     * @param array $data
     */
    public function loadHooks(array &$data)
    {
        $data = $this->getHookManager()->getModuleHookData($this->path . 'app/Hooks', $this->id, $data);
    }

    /**
     * Get className hash
     *
     * @param string $classesDir
     *
     * @return array
     */
    public function getClassNameHash(string $classesDir): array
    {
        // get files
        $fileList = $this
            ->container
            ->get('fileManager')
            ->getFileList($this->path . 'app/' . $classesDir, false, '\.php$', true);

        $result = [];
        if (!empty($fileList)) {
            foreach ($fileList as $item) {
                // prepare classname
                $className = str_replace('.php', '', $item);

                $result[$className] = "\\" . $this->id . "\\$classesDir\\$className";
            }
        }

        return $result;
    }

    /**
     * @return Unifier
     */
    protected function getUnifier(): Unifier
    {
        if (!isset($this->unifier)) {
            $this->unifier = new Unifier(
                $this->container->get('fileManager'),
                $this->container->get('metadata'),
                false
            );
        }

        return $this->unifier;
    }

    /**
     * @return Unifier
     */
    protected function getObjUnifier(): Unifier
    {
        if (!isset($this->objUnifier)) {
            $this->objUnifier = new Unifier(
                $this->container->get('fileManager'),
                $this->container->get('metadata'),
                true
            );
        }

        return $this->objUnifier;
    }

    /**
     * @return mixed
     */
    protected function getHookManager()
    {
        if (is_null($this->hookManager)) {
            $this->hookManager = (new HookManager($this->container))->load();
        }

        return $this->hookManager;
    }

    /**
     * @return Route
     */
    protected function getRouteUtil(): Route
    {
        if (is_null($this->routeUtil)) {
            $this->routeUtil = new Route(
                $this->container->get('config'),
                $this->container->get('metadata'),
                $this->container->get('fileManager'),
                $this->container->get('moduleManager')
            );
        }

        return $this->routeUtil;
    }
}
