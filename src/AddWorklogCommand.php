<?php namespace AmazeeLabs\alcli;

use Dotenv;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class AddWorklogCommand extends AlcliCommandBase
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('lw')
            ->setDescription('Log Work')
            ->setHelp("Log Work for an activity or to a JIRA Issue");

        $this->addArgument('issue', InputArgument::REQUIRED, 'Which activity / issue - this can be looked up from the team yml file');
        $this->addArgument('time', InputArgument::REQUIRED, 'The worklog time. EG: 1h 23m');
        $this->addArgument('comment', InputArgument::REQUIRED, 'The worklog comment. Be concise but descriptive');
  
        $this->addOption('date','',
          InputOption::VALUE_OPTIONAL,
          'The date the work was done');

        $this->addOption('list','',
          InputOption::VALUE_NONE,
          'Show the issues you can use');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setOutput($output);

        $issue = $input->getArgument('issue');
        $time = $input->getArgument('time');
        $comment = $input->getArgument('comment');

        $date = $input->getOption('date');
	if(!$date) {
		$date = date('Y-m-d') . 'T' . date('H:i:s') .'.000+0000';
	} else {
		$dt = $date;
		$date = date('Y-m-d',strtotime($date)) . 'T' . date('H:i:s',strtotime($date)) .'.000+0000';
	}


        try {
          self::prepareConfig($input->getOption('config'), FALSE);
        } catch(Exception $ex) {
          $output->writeln('<error>Error processing the application config: ' . $ex->getMessage() . '</error>');
          return AlcliCommandBase::ALCLI_CONFIG_FILE_ERROR;
        }

	if(!preg_match("/(\w+)-(\d+)/", $issue)) {
          $issue = strtolower($issue);
          if(!isset($this->config['issues'][$issue])) {
            $output->writeln('<error>Issue key not found: ' . $issue.'</error>');
            return self::QUERY_NOT_FOUND;
          }

	  $issue = $this->config['issues'][$issue];
 	}

        if($input->getOption('list')) {
          foreach($this->config['issues'] as $issueName => $issueKey) {
            $tableRows[] = [$issueName, $issueKey];
          }
	
	  $this->outputTable(array('Name', 'Issue Key'), $tableRows);
          return;
        }

	$output->writeln("Will try to log $time on $issue for $date with detail '$comment'");
	$JIRA = new Jira($this->config['endpoint'], $this->config['username'], $this->config['password']);
	$JIRA->addWorklog($issue, $time, $date, $comment);
    }
}
