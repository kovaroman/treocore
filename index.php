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

use Treo\Core\Application as App;
use Treo\Core\Portal\Application as PortalApp;

include "bootstrap.php";

// define gloabal variables
define('CORE_PATH', __DIR__);

// check PHP version
App::isPhpVersionValid();

// create  app
$app = new App();

if (!empty($_GET['entryPoint'])) {
    $app->runEntryPoint($_GET['entryPoint']);
    exit;
}

// prepare uri
$uri = (!empty($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : null;

if (!empty($id = PortalApp::getCallingPortalId())) {
    // create portal app
    $app = new PortalApp($id);
} elseif (!empty($uri) && $uri != '/') {
    // print module client file
    if (preg_match_all('/^\/client\/(.*)$/', $uri, $matches)) {
        $app->printModuleClientFile($matches[1][0]);
    }

    // if images path than call showImage
    if (preg_match_all('/^\/images\/(.*)\.(jpg|png|gif)$/', $uri, $matches)) {
        $app->runEntryPoint('TreoImage', ['id' => $matches[1][0], 'mimeType' => $matches[2][0]]);
    }

    // show 404
    header("HTTP/1.0 404 Not Found");
    exit;
}

$app->runClient();
