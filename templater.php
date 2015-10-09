<?php
/**
 * Templater v1.0.2
 *
 * This plugin enables you to use different templates on one site
 * individual set per page or collection.
 *
 * Dual licensed under the MIT or GPL Version 3 licenses, see LICENSE.
 * http://benjamin-regler.de/license/
 *
 * @package     Templater
 * @version     1.0.2
 * @link        <https://github.com/sommerregen/grav-plugin-templater>
 * @author      Benjamin Regler <sommerregen@benjamin-regler.de>
 * @copyright   2015, Benjamin Regler
 * @license     <http://opensource.org/licenses/MIT>        MIT
 * @license     <http://opensource.org/licenses/GPL-3.0>    GPLv3
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Twig\TraceableTwigEnvironment;

/**
 * TemplaterPlugin
 *
 * This plugin enables you to use different templates on one site individual
 * set per page or collection.
 */
class TemplaterPlugin extends Plugin
{
  /**
   * Return a list of subscribed events of this plugin.
   *
   * @return array    The list of events of the plugin of the form
   *                      'name' => ['method_name', priority].
   */
  public static function getSubscribedEvents()
  {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0],
    ];
  }

  /**
  * Initialize configuration
  */
  public function onPluginsInitialized()
  {
    if ($this->isAdmin()) {
      $this->active = false;
      return;
    }

    // Activate plugin only if 'enabled' option is set true
    if ($this->config->get('plugins.templater.enabled')) {
      $this->enable([
        'onPageInitialized' => ['onPageInitialized', 1000]
      ]);
    }
  }

  /**
   * Change template of page
   */
  public function onPageInitialized()
  {
    /** @var Page $page */
    $page = $this->grav['page'];

    $config = $this->mergeConfig($page);
    if ($config->get('enabled') && ($template = $this->mergeTemplateConfig($page))) {
      
      error_log( print_r( $page->header(), true ) );
      //LiQing Using root page's template setting if current page have not set
      if (!isset($page->header()->template)) {
        $page->template($template);
        error_log( print_r( $page->template(), true ) );
      }


      // Silent DebugBar error :: 'twig' is already a registered collector
      $enabled = $this->config->get('system.debugger.enabled');
      if ($enabled && ($debug = $this->config->get('system.debugger.twig', false))) {
        $this->config->set('system.debugger.twig', false);
      }

      // Reset and re-initialize Twig environment
      $twig = $this->grav['twig'];
      $twig->twig = null;
      $twig->twig_paths = [];
      $twig->init();

      // Update TwigCollector for DebugBar
      if ($enabled && $debug) {
        $twig->twig = new TraceableTwigEnvironment($twig->twig);
        $collector = $this->grav['debugger']->getCollector('twig');
        $collector->twig = $twig->twig;
      }
    }
  }

  /**
   * Merge global and page template settings
   *
   * @param Page  $page    The page to merge the page template configurations
   *                       with the template settings.
   * @param bool  $default The default value in case no template setting was
   *                       found.
   *
   * @return array
   */
  protected function mergeTemplateConfig(Page $page, $default = null)
  {
    while ($page && !$page->root()) {
      if (isset($page->header()->template)) {
        $template = $page->header()->template;
        if ($template === '@default') {
          $template = $default;
        }

        return $template;
      }
      $page = $page->parent();
    }

    return $default;
  }

}
