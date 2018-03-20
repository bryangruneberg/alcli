<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraListIssueWorklogsCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:lsw {issue : The issue to list} {--style=table : table or csv output}';
    protected $description = 'List JIRA issue worklogs';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issue = app(Jira::class)->getIssue($issueKey);

        $headers = ['Date', 'Author', 'Comment', 'Worked'];
        $rows = [];

        $wrap = intval($this->consoleWidth() / 4);
        $workLogs = app(Jira::class)->getIssueWorklogs($issueKey);

        if(count($workLogs) <= 0) 
        {
            $this->error("No worklogs found for the requested period");
            return;
        }

        $data = $issue->all()->only(['description','summary','assignee','reporter','status','issuetype'])->toArray();
        $this->drawLine();
        $this->comment($data['issuetype'] . ' ' . $issueKey . ' | ' . $data['status'] . ' | By: ' . $data['reporter'] .' | Assigned to: ' . $data['assignee']);
        $this->drawLine();
        $this->info($data['summary']);
        $this->line("");

        $totalSecWorked = 0;
        $tableRows = [];
        foreach($workLogs as $workLog) {
            $data = $workLog->all()->only(['author','timespentseconds', 'comment', 'started'])->toArray();

            if(!isset($data['timespentseconds'])) 
            {
                $data['timespentseconds'] = 0;
            }

            $totalSecWorked += intval($data['timespentseconds']);

            $worked = isset($data['timespentseconds']) ? $this->secToHM($data['timespentseconds']) : '';
            $comment = str_replace("\r","", $data['comment']);
            $comment = str_replace("\n","", $comment);
            $comment = wordwrap($comment, $wrap);

            $dt = new \DateTime($data['started']);

            $tableRows[] = array($dt->format('Y-m-d H:i'), $data['author'], $comment, $worked);
        }

        if(count($tableRows) > 1 && ($totalSecWorked)) {
            $tableRows[] = array("" ,"","", $this->secToHM($totalSecWorked));
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
