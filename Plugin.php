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

        /**
         * Este hook dispara a cada request, logo antes do App resolver qual
         * controller/action chamar, e recebe as variáveis por referência. O
         * único detalhe é que o alias 'lista' => 'list' do routes.php é
         * aplicado antes do hook, então a verificação checa action_name ===
         * 'list'.
         */
        $this->app->hook('routes.filter', function (&$controller_id, &$action_name) {
            // 'lista' é resolvido para 'list' pelo alias de actions antes deste hook disparar
            if ($controller_id === 'pc' && $action_name === 'list') {
                $controller_id = 'bigsheet';
                $action_name = 'opportunitiesWithDiligence';
            }
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
        if (!$this->app->user->isUserAdmin($this->app->user)) {
            return;
        }
        $this->app->view->enqueueStyle('app','bigsheet-style', 'css/bigsheet.css');
        $this->app->view->part('bigsheet/tab-import');
        $this->app->view->enqueueScript('app','bigsheet-script', 'js/bigsheet.js');
    }
}
