<?php namespace AmazeeLabs\alcli;

use Dotenv;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListIssuesCommand extends AlcliCommandBase
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ls')
            ->setDescription('List Issues')
            ->setHelp("List JIRA Issues");

        $this->addArgument('named-query', InputArgument::OPTIONAL, 'Which query?');
  
        $this->addOption('list','',
          InputOption::VALUE_NONE,
          'List the available queries');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setOutput($output);

        $namedQuery = $input->getArgument('named-query');
	if(!$namedQuery) {
          $namedQuery = "mine";
        }

        try {
          self::prepareConfig($input->getOption('config'), FALSE);

          if(!isset($this->config['queries'])) {
            throw new Exception('The following config keys are required: queries');
          }
        } catch(Exception $ex) {
          $output->writeln('<error>Error processing the application config: ' . $ex->getMessage() . '</error>');
          return AlcliCommandBase::ALCLI_CONFIG_FILE_ERROR;
        }

        if(!isset($this->config['queries'][$namedQuery])) {
          $output->writeln('<error>Query not found: ' . $namedQuery.'</error>');
          return self::QUERY_NOT_FOUND;
        }

        if($input->getOption('list')) {
          foreach($this->config['queries'] as $queryName => $query) {
            $tableRows[] = [$queryName, trim($query)];
          }
	
	  $this->outputTable(array('Name', 'Query'), $tableRows);
          return;
        }

        $query = $this->config['queries'][$namedQuery];
	$JIRA = new Jira($this->config['endpoint'], $this->config['username'], $this->config['password']);
        $issues = $JIRA->getIssues($query);  

	$tableRows = [];
	foreach($issues as $issue) {
	  $tableRows[] = array($issue->getKey(), $issue->get('Summary'));
	}

	$this->outputTable(array('Key', 'Summary'), $tableRows);
    }

}
