<?php

require_once ROOT.'/vendor/autoload.php';
require_once __DIR__.'/../config/pathServer.php';

use Restfull\Http\Server;

echo (new Server())->execute()->send();