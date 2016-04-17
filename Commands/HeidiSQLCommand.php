<?php
namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;
use Terminus\Utils;

/**
 * Open Site database in HeidiSQL
 *
 * @command site
 */
class HeidiSQLCommand extends TerminusCommand {
  /**
   * Object constructor
   *
   * @param array $options
   * @return PantheonAliases
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
     parent::__construct($options);
     $this->sites = new Sites();
  }

   /**
   * Open Site database in HeidiSQL
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site to Use
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site heidisql --site=my-site --env=dev
   *
   * @subcommand heidisql
   * @alias heidi
   */
  public function heidisql($args, $assoc_args) {
    // Check if OS is Windows
    if (!Utils\isWindows()) {
      $this->failure('Operating system is not supported.');
    }

    $site = $this->sites->get(
      $this->input()->siteName(array('args' => $assoc_args))
    );

    $env = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $domain = $env . '-' . $site->get('name') . '.pantheon.io';
    $environment = $site->environments->get($env);
    $connection_info = $environment->connectionInfo();

    $mysql_host = escapeshellarg($connection_info['mysql_host']);
    $mysql_username = escapeshellarg($connection_info['mysql_username']);
    $mysql_password = escapeshellarg($connection_info['mysql_password']);
    $mysql_port = escapeshellarg($connection_info['mysql_port']);

    $possible_heidi_locations = array(
      '\Program Files\HeidiSQL\heidisql.exe',
      '\Program Files (x86)\HeidiSQL\heidisql.exe',
      getenv('TERMINUS_PANCAKES_HEIDISQL_LOC'),
    );

    foreach ($possible_heidi_locations as $phl) {
      if (file_exists($phl)) {
        $app = escapeshellarg($phl);
        $this->log()->info('Opening {domain} database in {app}', array('domain' => $domain, 'app' => $app));
        // Wake the Site
        $environment->wake();
        $command = sprintf('start /b "" %s -h=%s -P=%s -u=%s -p=%s', $app, $mysql_host, $mysql_port, $mysql_username, $mysql_password);
        $this->log()->info($command);
        exec($command);
        break;
      }
    }
  }
}
