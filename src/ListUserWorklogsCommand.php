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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setOutput($output);

        $user = $input->getOption('user');

        try {
          self::prepareConfig($input->getOption('config'), FALSE);

        } catch(Exception $ex) {
          $output->writeln('<error>Error processing the application config: ' . $ex->getMessage() . '</error>');
          return AlcliCommandBase::ALCLI_CONFIG_FILE_ERROR;
        }

	$JIRA = new Jira($this->config['endpoint'], $this->config['username'], $this->config['password']);
        $workLogs = $JIRA->getTempoWorklogs($user, $input->getOption('from'), $input->getOption('to'));  

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

	  $tableRows[] = array($workLog['dateStarted'], $workLog['issue']['key'], $worked, $billed, $comment);
	}
        
        if($totalSecWorked || $totalSecBilled) {
	  $tableRows[] = array("", "" , Utils::secToHM($totalSecWorked), Utils::secToHM($totalSecBilled), "");
        }

	$this->outputTable(array('Date', 'Key', 'Worked', 'Billed', 'Comment'), $tableRows);
    }

}
