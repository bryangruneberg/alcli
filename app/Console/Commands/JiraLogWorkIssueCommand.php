<?php namespace App\Console\Commands;

use App\Jira;
use App\Worklog;
use App\Issue;
use Illuminate\Console\Command;

class JiraLogWorkIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:lw {issue : The issue on which to log work} {--comment= : The comment for the worklog} {--date= : The date the work was done} {--time= : The time spent working} {--list} {--force}';
    protected $description = 'Log work on a JIRA issue';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issueKey = $this->getIssueKey($issueKey);
        $issue = app(Jira::class)->getIssue($issueKey);

        if($this->option('list')) 
        {
            $config = app(Jira::class)->resolveConfigArray();
            if(isset($config['issues'])) 
            {
                $issues = $config['issues'];
                $tableRows = [];
                foreach($issues as $k => $v) 
                {
                    $tableRows[] = array($k, $v);
                }

                $this->table(['Shortcut','Issue Key'], $tableRows);
            }
            return;
        }

        if(!$issue)
        {
            $this->error('The requested issue ' . $issueKey . ' cannot be loaded');
            return;
        }

        $loggit = FALSE;
        if($this->option('force')) {
            if(!$this->option('comment')) 
            {
                $this->error('There is no comment');
                return;
            }

            if(!$this->option('time')) 
            {
                $this->error('There is no time spent');
                return;
            }

            $loggit = TRUE;
        }

        $date = $this->option('date');
        if(!$date) {
            $date = date('Y-m-d') . 'T' . date('H:i:s') .'.000+0000';
        } else {
            $date = date('Y-m-d',strtotime($date)) . 'T' . date('H:i:s',strtotime($date)) .'.000+0000';
        }
        $this->info("Date: " . $date);

        $timeSpent = $this->option('time');
        if(!$timeSpent) 
        {
            $timeSpent = $this->ask('Time Spent');
        }


        $comment = $this->option('comment');
        if(!$comment) 
        {
            $comment = $this->ask('Comment');
        }

        while(!$loggit) {
            $this->drawLine();
            $this->info($issueKey);
            $this->line("Date: " . $date);
            $this->line("Time Spent: " . $timeSpent);
            $this->line("Comment: " . $comment);
            $this->drawLine();
            $choice = $this->choice("Shall we log the time?", ["Yes", "No", "Edit"]);
            switch($choice) {
            case "Yes":
                $loggit = TRUE;
                break;
            case "Edit":
                $comment = $this->ask('Comment', $comment);

                $date = $this->ask('Date', $date);
                $date = date('Y-m-d',strtotime($date)) . 'T' . date('H:i:s',strtotime($date)) .'.000+0000';

                $timeSpent = $this->ask('Time Spent', $timeSpent);
                break;
            case "No":
            default:
            $this->error("Cancelled.");
            return;
            }
        }
        
        $tlRes = app(Jira::class)->addWorklog($issueKey, $timeSpent, $date, $comment);
        $tlRaw = $tlRes->getResult();

        $tl = app(Worklog::class);
        $tl->fill($issueKey, $tlRaw);
        $data = $tl->all()->only(['author','timespent','started','comment'])->toArray();

        $this->table([], [['Author',$data['author']], ['Time Spent', $data['timespent']], ['Date', $data['started']], ['Comment', $data['comment']]]);
    }
}
