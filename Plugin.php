<?php

namespace BigSheetImporter;

use BigSheetImporter\Controllers\Controller;
use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init()
    {
        $app = App::i();


    }

    /**
     * @throws \Exception
     */
    function register()
    {
        $app = App::i();
        $app->registerController('bigsheet', Controller::class);
    }
}
