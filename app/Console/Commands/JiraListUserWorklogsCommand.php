<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraListUserWorklogsCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:lsuw {--user=} {--from=} {--to=} {--day=today : Report on a specific day} {--month} {--week} {--style=table : table or csv output}';
    protected $description = 'List JIRA user worklogs';

    public function handle()
    {
        $user = $this->getUsername($this->option('user'));
        $this->info("User: " . $user);

        $from = $this->option('from');
        $to = $this->option('to');
        $day = $this->option('day');

        if($this->option('day')) {
            $from = $day;
            $to = $day;
        }

        if($this->option('month')) {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }

        if($this->option('week')) {
            $from = date('Y-m-d', strtotime("previous monday"));
            $to = date('Y-m-d', strtotime("sunday"));
        }

        if(!$from) {
            $from = "previous monday";
        }

        if(!$to) {
            $to = "sunday";
        }

        $from = date('Y-m-d', strtotime($from));
        $to = date('Y-m-d', strtotime($to));

        $this->info($from . " => " . $to);

        $headers = ['Date', 'Key', 'Worked', 'Billed', 'Comment'];
        $rows = [];

        $wrap = intval($this->consoleWidth() / 2);
        $workLogs = app(Jira::class)->getTempoUserWorklogs($user, $from, $to);

        if(count($workLogs) <= 0) 
        {
            $this->error("No worklogs found for the requested period");
            return;
        }

        $totalSecWorked = 0;
        $totalSecBilled = 0;
        $tableRows = [];
        foreach($workLogs as $workLog) {
            $data = $workLog->all()->only(['issue_key','author','timespentseconds','billedseconds', 'comment', 'datestarted'])->toArray();

            if(!isset($data['timespentseconds'])) 
            {
                $data['timespentseconds'] = 0;
            }

            if(!isset($data['billedseconds']))
            {
                $data['billedseconds'] = 0;
            }

            $totalSecWorked += intval($data['timespentseconds']);
            $totalSecBilled += intval($data['billedseconds']);

            $worked = isset($data['timespentseconds']) ? $this->secToHM($data['timespentseconds']) : '';
            $billed = isset($data['billedseconds']) ? $this->secToHM($data['billedseconds']) : '';
            $comment = $this->simplifyAndWrap($data['comment'], $wrap);

            $dt = new \DateTime($data['datestarted']);

            $tableRows[] = array($dt->format('Y-m-d H:i'), $data['issue_key'], $worked, $billed, $comment);
        }

        if(count($tableRows) > 1 && ($totalSecWorked || $totalSecBilled)) {
            $tableRows[] = array("", "" , $this->secToHM($totalSecWorked), $this->secToHM($totalSecBilled), "");
        }

        switch($this->option('style')) 
        {
        case 'csv':
            $this->csv($headers, $tableRows);
            break;
        case 'table':
            $this->table($headers, $tableRows);
            break;
        default:
        }
    }
}
