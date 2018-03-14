<?php namespace AmazeeLabs\alcli;

use Dotenv;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Helper\Table;

abstract class AlcliCommandBase extends Command
{

    const ALCLI_CONFIG_FILE_ERROR = 255;
    const ALCLI_CODE_DIR_ERROR = 254;
    const ALCLI_CONFIG_FILE_DEFAULT = "alcli.yml";

    protected $config = [];

    protected $dotenv = NULL;
    protected $output = NULL;

    protected function setOutput($output) {
      $this->output = $output;
    }

    protected function logln($line, $type = "INFO", $time = NULL) {
      if(!$time || !is_numeric($time)) {
        $time = time();
      }

      $typeStr = '';
      if($type) {
        $typeStr = $type  . ' ' ;
      }
      
      $timeStr = date('Y-m-d H:i:s', $time);
      $this->output->writeln($timeStr . '] ' . $typeStr . $line);
    }

    protected function configure()
    {
        $this->addOption('config','c',
          InputOption::VALUE_REQUIRED,
          'Specify a config file path',
          getcwd() . "/" . self::ALCLI_CONFIG_FILE_DEFAULT);
    }

    protected function prepareConfig($configFilePath = NULL, $requireConfigFile = TRUE)
    {
        if(is_null($configFilePath)) {
          $configFilePath = self::ALCLI_CONFIG_FILE_DEFAULT;
        }

        $configFilePath = Utils::processPath($configFilePath);
        $configFileInfo = pathinfo($configFilePath);

        if($requireConfigFile && !file_exists($configFilePath)) {
          throw new Exception($configFilePath . " not found");
        }

        if(file_exists($configFilePath)) {
          $this->config = Yaml::parse(file_get_contents($configFilePath));
        } else {
          $this->config = [];
        }

        $this->config['config'] = $configFilePath;

        // Load up the environment
        if(file_exists(getcwd() . "/.env")) {
          $this->dotenv = new Dotenv\Dotenv(getcwd());
          $this->dotenv->load();
        }
	
	if(isset($this->config['team'])) {
		$teamConfigFile = $configFileInfo['dirname'] . '/' . $this->config['team'] . '.yml';
		if(file_exists($teamConfigFile)) {
		  $this->team_config = Yaml::parse(file_get_contents($teamConfigFile));
		  if(isset($this->team_config['queries'])) {
		    $this->config['queries'] = array_merge($this->config['queries'], $this->team_config['queries']);
		  }
		}
	}
    }

    public function outputTable($headers, $rows) {
          $table = new Table($this->output);
          $table
            ->setHeaders($headers)
            ->setRows($rows);

          $table->render();
    }
}
