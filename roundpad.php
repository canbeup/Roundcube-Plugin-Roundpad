<?php
/**
 * Roundpad plugin
 *
 * @author Thomas Payen <thomas.payen@apitech.fr>
 * @author Aleksander Machniak <machniak@kolabsys.com>
 *
 * This plugin is based on kolab_files plugin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class roundpad extends rcube_plugin
{
    // all task excluding 'login' and 'logout'
    public $task = '?(?!login|logout).*';

    public $rc;
    public $home;
    private $engine;

    public function init()
    {
        $this->rc = rcube::get_instance();

        // Chargement de la conf
        $this->load_config();

        // Gestion du filtre LDAP
        $filter_ldap = $this->rc->config->get('roundcube_owncloud_filter_ldap', array());
        if (isset($filter_ldap) && count($filter_ldap) > 0) {
          $user_infos = LibMelanie\Ldap\Ldap::GetUserInfos($this->rc->get_user_name());

          foreach ($filter_ldap as $key => $value) {
            if (is_array($user_infos[$key]) && !in_array($value, $user_infos[$key]) || is_string($user_infos[$key]) &&  $user_infos[$key] != $value) {
              return;
            }
          }
        }

        // Register hooks
        $this->add_hook('refresh', array($this, 'refresh'));

        // Plugin actions for other tasks
        $this->register_action('plugin.roundpad', array($this, 'actions'));

        // Register task
        $this->register_task('roundpad');

        // Register plugin task actions
        $this->register_action('index', array($this, 'actions'));
        $this->register_action('prefs', array($this, 'actions'));
        $this->register_action('open',  array($this, 'actions'));
        $this->register_action('file_api', array($this, 'actions'));

        // Load UI from startup hook
        $this->add_hook('startup', array($this, 'startup'));
    }

    /**
     * Creates roundpad_engine instance
     */
    private function engine()
    {
        if ($this->engine === null) {
            $this->load_config();


            require_once $this->home . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'roundpad_files_engine.php';

            $this->engine = new roundpad_files_engine($this);
        }

        return $this->engine;
    }

    /**
     * Startup hook handler, initializes/enables Files UI
     */
    public function startup($args)
    {
        // call this from startup to give a chance to set
        $this->ui();
    }

    /**
     * Adds elements of files API user interface
     */
    private function ui()
    {
        if ($this->rc->output->type != 'html') {
            return;
        }

        if ($engine = $this->engine()) {
            $engine->ui();
        }
    }

    /**
     * Refresh hook handler
     */
    public function refresh($args)
    {
        // Here we are refreshing API session, so when we need it
        // the session will be active
        if ($engine = $this->engine()) {
        }

        return $args;
    }

    /**
     * Engine actions handler
     */
    public function actions()
    {
        if ($engine = $this->engine()) {
            $engine->actions();
        }
    }
}
