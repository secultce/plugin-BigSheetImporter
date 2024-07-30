<?php

namespace BigSheetImporter;

use BigSheetImporter\Controllers\Controller;
use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    /**
     * @var App
     */
    private $app;

    public function _init()
    {
        $this->app = App::i();

        $plugin = $this;
        $this->app->hook('template(panel.opportunities.tab-avaliacoes):after', function () use ($plugin) {
            $plugin->tabImport();
        });

    }

    /**
     * @throws \Exception
     */
    function register()
    {
        $this->app->registerController('bigsheet', Controller::class);
    }

    private function tabImport()
    {
        $this->app->view->enqueueStyle('app','bigsheet-style', 'css/bigsheet.css');
        $this->app->view->part('bigsheet/tab-import');
        $this->app->view->enqueueScript('app','bigsheet-script', 'js/bigsheet.js');
    }
}
