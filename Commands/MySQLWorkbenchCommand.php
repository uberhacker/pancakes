<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Models\Collections\Sites;

/**
 * Open Site database in MySQL Workbench
 *
 * @command site
 */
class MySQLWorkbenchCommand extends TerminusCommand {
  /**
   * Object constructor
   *
   * @param array $options
   * @return MySQLWorkbenchCommand
   */

  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Open Site database in MySQL Workbench
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Site Name
   *
   * [--env=<env>]
   * : Environment
   *
   * ## EXAMPLES
   *  terminus site mysql-workbench --site=my-site --env=dev
   *
   * @subcommand mysql-workbench
   * @alias workbench
   */
  public function mysqlworkbench($args, $assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(array('args' => $assoc_args))
    );

    $env = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
    $environment = $site->environments->get($env);
    $connection_info = $environment->connectionInfo();

    // Additional connection information
    $sftp_port = 2222;
    $domain = $env . '-' . $site->get('name') . '.pantheon.io';
    $connection_info['domain'] = $domain;
    $parts = explode(':', $connection_info['sftp_url']);
    if (isset($parts[2])) {
      $sftp_port = $parts[2];
    }
    $connection_info['sftp_port'] = $sftp_port;
    $connection_info['connection_id'] = substr(md5($domain . '.connection'), 0, 8)
                                          . '-' . $site->get('id');
    $connection_info['server_instance_id'] = substr(md5($domain . '.server'), 0, 8)
                                               . '-' . $site->get('id');

    // Determine the command and configuration directory based on the operating system
    $os = strtoupper(substr(PHP_OS, 0, 3));
    $workbench = getenv('TERMINUS_PANCAKES_MYSQLWORKBENCH_LOC');
    switch ($os) {
      case 'DAR':
        if (!$workbench) {
          $workbench = '/Applications/MySQLWorkbench.app/Contents/MacOS/MySQLWorkbench';
        }
        $workbench_cmd = "$workbench --admin";
        $workbench_cfg = getenv('HOME') . '/Library/Application Support/MySQL/Workbench/';
        $redirect = '> /dev/null 2> /dev/null &';
          break;
      case 'LIN';
        if (!$workbench) {
          $workbench = '/usr/bin/mysql-workbench';
        }
        $workbench_cmd = "$workbench --admin";
        $workbench_cfg = getenv('HOME') . '/.mysql/workbench/';
        $redirect = '> /dev/null 2> /dev/null &';
          break;
      case 'WIN':
        if (!$workbench) {
	  $candidates = array(
	    'C:\\\\Program Files\\\\MySQL\\\\MySQL Workbench 6.3 CE\\\\MySQLWorkbench.exe',
	    'C:\\\\Program Files (x86)\\\\MySQL\\\\MySQL Workbench 6.3 CE\\\\MySQLWorkbench.exe',
	  );
	  foreach ($candidates as $candidate) {
	    if (file_exists($candidate)) {
	      $workbench = $candidate;
	      break;
	    }
	  }
	  if (!$workbench) {
	    $this->failure('Unable to locate MySQLWorkbench.exe');
	  }
        }
        $workbench_cmd = "\"$workbench\" -admin";
        $workbench_cfg = getenv('HOME') . '/AppData/Roaming/MySQL/Workbench/';
        $redirect = '';
          break;
      default:
        $this->failure('Operating system not supported.');
    }

    $this->log()->info(
      'Opening {domain} database in {app}',
      array('domain' => $domain, 'app' => $workbench)
    );

    // Connections XML configuration file
    $connections_xml = $this->getConnection($connection_info);
    $connections_file = "{$workbench_cfg}connections.xml";
    $this->writeXml($connections_file, $connections_xml, $domain);

    // Server instances XML configuration file
    $server_instances_xml = $this->getServerInstance($connection_info);
    $server_instances_file = "{$workbench_cfg}server_instances.xml";
    $this->writeXml($server_instances_file, $server_instances_xml, $domain);

    // Wake the Site
    $environment->wake();

    // Open in MySQL Workbench
    $command = sprintf('%s %s %s', $workbench_cmd, $domain, $redirect);
    $this->log()->info($command);
    if ($this->validCommand($workbench)) {
      exec($command);
    }
  }

  /**
   * Generate the XML for opening a connection in MySQL Workbench
   *
   * @param array $ci Connection information array
   * @return string XML configuration file section
   */
  private function getConnection($ci) {
    return <<<XML
    <value type="object" struct-name="db.mgmt.Connection" id="{$ci['connection_id']}" struct-checksum="0x96ba47d8">
      <link type="object" struct-name="db.mgmt.Driver" key="driver">com.mysql.rdbms.mysql.driver.native_sshtun</link>
      <value type="string" key="hostIdentifier">Mysql@{$ci['mysql_host']}:{$ci['mysql_port']}@{$ci['sftp_host']}:{$ci['sftp_port']}</value>
      <value type="int" key="isDefault">1</value>
      <value _ptr_="0x321bf00" type="dict" key="modules"/>
      <value _ptr_="0x321bf70" type="dict" key="parameterValues">
        <value type="string" key="DbSqlEditor:LastDefaultSchema">{$ci['mysql_database']}</value>
        <value type="string" key="SQL_MODE"></value>
        <value type="string" key="hostName">{$ci['mysql_host']}</value>
        <value type="int" key="lastConnected"></value>
        <value type="string" key="password">{$ci['mysql_password']}</value>
        <value type="int" key="port">{$ci['mysql_port']}</value>
        <value type="string" key="schema">{$ci['mysql_database']}</value>
        <value type="string" key="serverVersion">10.0.21-MariaDB-log</value>
        <value type="string" key="sshHost">{$ci['sftp_host']}:{$ci['sftp_port']}</value>
        <value type="string" key="sshKeyFile"></value>
        <value type="string" key="sshPassword"></value>
        <value type="string" key="sshUserName">{$ci['sftp_username']}</value>
        <value type="string" key="sslCA"></value>
        <value type="string" key="sslCert"></value>
        <value type="string" key="sslCipher"></value>
        <value type="string" key="sslKey"></value>
        <value type="int" key="useSSL">1</value>
        <value type="string" key="userName">{$ci['mysql_username']}</value>
      </value>
      <value type="string" key="name">{$ci['domain']}</value>
      <link type="object" struct-name="GrtObject" key="owner">d460176e-fabd-11e5-874c-f0761c1cdeaf</link>
    </value>
XML;
  }

  /**
   * Generate the XML for opening a server instance in MySQL Workbench
   *
   * @param array $ci Connection information array
   * @return string XML configuration file section
   */
  private function getServerInstance($ci) {
    return <<<XML
    <value type="object" struct-name="db.mgmt.ServerInstance" id="{$ci['server_instance_id']}" struct-checksum="0x367436e2">
      <link type="object" struct-name="db.mgmt.Connection" key="connection">{$ci['connection_id']}</link>
      <value _ptr_="0x3218b80" type="dict" key="loginInfo"/>
      <value _ptr_="0x32067c0" type="dict" key="serverInfo">
        <value type="int" key="setupPending">1</value>
      </value>
      <value type="string" key="name">{$ci['domain']}</value>
    </value>
XML;
  }

  /**
   * Write the XML to the configuration file
   *
   * @param string $file The full path to the configuration file
   * @param string $xml The XML configuration file section contents
   * @param string $domain The fully qualified domain of the Pantheon site
   */
  private function writeXml($file, $xml, $domain) {
    $data = file_get_contents($file);
    if (!strpos($data, $domain)) {
      $lines = file($file);
      $last = count($lines) - 1;
      unset($lines[$last]);
      if (count($lines) == 3) {
        $lines[2] = str_replace('/>', '>', $lines[2]);
      } else {
        $last = count($lines) - 1;
        unset($lines[$last]);
      }
      $end = "\n  </value>\n</data>";
      $data = implode('', $lines) . $xml . $end;
      $handle = fopen($file, "w");
      fwrite($handle, $data);
      fclose($handle);
    }
  }

  /**
   * Executable file validation
   *
   * @param string $file Full path to the executable file
   * @return bool True or false based on the file execution status
   */
  private function validCommand($file = '') {
    if (!$file) {
      return false;
    }
    if (!file_exists($file)) {
      $this->failure("$file does not exist.");
      return false;
    }
    if (!is_executable($file)) {
      $this->failure("$file is not executable.");
      return false;
    }
    return true;
  }

}
