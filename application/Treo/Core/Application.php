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

use Espo\Core\Application as Base;
use Treo\Services\Installer;
use Treo\Core\Utils\Auth;

/**
 * Class Application
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Application extends Base
{
    /**
     * Is PHP version valid ?
     */
    public static function isPhpVersionValid()
    {
        // prepare data
        $validPhpVersion = '7.1';

        // prepare PHP version
        $versionData = explode(".", phpversion());
        $phpVersion = $versionData[0] . "." . $versionData[1];

        // validate PHP version
        if (version_compare($phpVersion, $validPhpVersion, '<')) {
            echo "Invalid PHP version. PHP 7.1 or above is required!";
            die();
        }
    }

    /**
     * @inheritdoc
     */
    public function isInstalled()
    {
        // copy config if it needs
        $this->copyDefaultConfig();

        return parent::isInstalled();
    }

    /**
     * Run console
     *
     * @param array $argv
     */
    public function runConsole(array $argv)
    {
        // unset file path
        if (isset($argv[0])) {
            unset($argv[0]);
        }

        $this
            ->getContainer()
            ->get('consoleManager')
            ->run(implode(' ', $argv));
    }

    /**
     * @inheritdoc
     */
    public function run($name = 'default')
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerApi();
        }

        parent::run($name);
    }

    /**
     * @inheritdoc
     */
    public function runClient()
    {
        // for installer
        if (!$this->isInstalled()) {
            $this->runInstallerClient();
        }

        parent::runClient();
    }

    /**
     * @param string $file
     */
    public function printModuleClientFile(string $file)
    {
        foreach (array_reverse($this->getContainer()->get('moduleManager')->getModules()) as $module) {
            $path = $module->getClientPath() . $file;
            if (file_exists($path)) {
                $parts = explode(".", $path);

                switch (array_pop($parts)) {
                    case 'css':
                        header('Content-Type: text/css');
                        break;
                    case 'js':
                        header('Content-Type: application/javascript');
                        break;
                    case 'json':
                        header('Content-Type: application/json');
                        break;
                    case 'png':
                        header('Content-Type: image/png');
                        break;
                    case 'jpeg':
                        header('Content-Type: image/jpeg');
                        break;
                    case 'jpg':
                        header('Content-Type: image/jpg');
                        break;
                    case 'gif':
                        header('Content-Type: image/gif');
                        break;
                    case 'ico':
                        header('Content-Type: image/vnd.microsoft.icon');
                        break;
                    case 'svg':
                        header('Content-type: image/svg+xml');
                        break;
                }
                echo file_get_contents($path);
                exit;
            }
        }

        // show 404
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    /**
     * @inheritdoc
     */
    protected function initContainer()
    {
        $this->container = new Container();
    }

    /**
     * Create auth
     *
     * @return Auth
     */
    protected function createAuth()
    {
        return new Auth($this->getContainer());
    }

    /**
     * @inheritdoc
     */
    protected function getRouteList()
    {
        $routes = new \Treo\Core\Utils\Route(
            $this->getConfig(),
            $this->getMetadata(),
            $this->getContainer()->get('fileManager'),
            $this->getContainer()->get('moduleManager')
        );

        return $routes->getAll();
    }

    /**
     * Run API for installer
     */
    protected function runInstallerApi()
    {
        // prepare request
        $request = $this->getSlim()->request();

        // prepare action
        $action = str_replace("/Installer/", "", $request->getPathInfo());

        // call controller
        $result = $this
            ->getContainer()
            ->get('controllerManager')
            ->process('Installer', $action, [], $request->getBody(), $request);

        header('Content-Type: application/json');
        echo $result;
        exit;
    }

    /**
     * Run client for installer
     */
    protected function runInstallerClient()
    {
        $result = ['status' => false, 'message' => ''];

        // check permissions and generate config
        try {
            /** @var Installer $installer */
            $installer = $this->getContainer()->get('serviceFactory')->create('Installer');
            $result['status'] = $installer->checkPermissions();
            $result['status'] = $installer->generateConfig() && $result['status'];
        } catch (\Exception $e) {
            $result['status'] = 'false';
            $result['message'] = $e->getMessage();
        }

        // prepare vars
        $vars = [
            'applicationName' => 'TreoCore',
            'status'          => $result['status'],
            'message'         => $result['message']
        ];

        $this->getContainer()->get('clientManager')->display(null, 'html/installation.html', $vars);
        exit;
    }

    /**
     * Copy default config
     */
    private function copyDefaultConfig(): void
    {
        // prepare config path
        $path = 'data/config.php';

        if (!file_exists($path)) {
            // get default data
            $data = include 'application/Treo/Configs/defaultConfig.php';

            // prepare salt
            $data['passwordSalt'] = mb_substr(md5((string)time()), 0, 9);

            // get content
            $content = "<?php\nreturn " . $this->getContainer()->get('fileManager')->varExport($data) . ";\n?>";

            // create config
            file_put_contents($path, $content);
        }
    }
}
