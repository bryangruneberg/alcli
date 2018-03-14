<?php namespace AmazeeLabs\alcli;

use Dotenv;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListUserWorklogsCommand extends AlcliCommandBase
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('lsuw')
            ->setDescription('List User Worklogs')
            ->setHelp("List JIRA User Worklogs");

        $this->addOption('user','',
          InputOption::VALUE_OPTIONAL,
          'Which user?');

        $this->addOption('from','',
          InputOption::VALUE_OPTIONAL,
          'Logs starting from');

        $this->addOption('to','',
          InputOption::VALUE_OPTIONAL,
          'Logs going to');

        $this->addOption('month','',
          InputOption::VALUE_NONE,
          'Gets the while month worth of data');

        $this->addOption('week','',
          InputOption::VALUE_NONE,
          'Gets the while month week of data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setOutput($output);

        $user = $input->getOption('user');
        $from = $input->getOption('from');
        $to = $input->getOption('to');

	if($input->getOption('month')) {
		$from = date('Y-m-01');
		$to = date('Y-m-t');
	}

	if($input->getOption('week')) {
		$from = date('Y-m-d', strtotime("previous monday"));
		$to = date('Y-m-d', strtotime("sunday"));
	}

	if(!$from) {
		$from = date('Y-m-d', strtotime("previous monday"));
	}

	if(!$to) {
		$to = date('Y-m-d', strtotime("sunday"));
	}

        try {
          self::prepareConfig($input->getOption('config'), FALSE);
        } catch(Exception $ex) {
          $output->writeln('<error>Error processing the application config: ' . $ex->getMessage() . '</error>');
          return AlcliCommandBase::ALCLI_CONFIG_FILE_ERROR;
        }

	$JIRA = new Jira($this->config['endpoint'], $this->config['username'], $this->config['password']);
        $workLogs = $JIRA->getTempoWorklogs($user, $from, $to);  

	$tableRows = [];
	$totalSecWorked = 0;
	$totalSecBilled = 0;
	foreach($workLogs as $workLog) {
	  $totalSecWorked += intval($workLog['timeSpentSeconds']);
	  $totalSecBilled += intval($workLog['billedSeconds']);

	  $worked = isset($workLog['timeSpentSeconds']) ? Utils::secToHM($workLog['timeSpentSeconds']) : '';
	  $billed = isset($workLog['billedSeconds']) ? Utils::secToHM($workLog['billedSeconds']) : '';
	  $comment = str_replace("\r","", $workLog['comment']);
	  $comment = str_replace("\n","", $comment);
	  $comment = substr($comment, 0, 100);

	  $dt = new \DateTime($workLog['dateStarted']);

	  $tableRows[] = array($dt->format('Y-m-d H:i'), $workLog['issue']['key'], $worked, $billed, $comment);
	}
        
        if($totalSecWorked || $totalSecBilled) {
	  $tableRows[] = array("", "" , Utils::secToHM($totalSecWorked), Utils::secToHM($totalSecBilled), "");
        }

	$output->writeln('From: ' . $from);
	$output->writeln('To: ' . $to);
	$this->outputTable(array('Date', 'Key', 'Worked', 'Billed', 'Comment'), $tableRows);
    }

}
