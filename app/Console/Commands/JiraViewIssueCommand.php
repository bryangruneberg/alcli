<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraViewIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:cat {issue : The issue to view} {--style=table : table or csv output} {--worklogs : Include the worklogs} {--comments : Include comments} {--data=no : | Include field data}';
    protected $description = 'View JIRA issue';

    public function handle()
    {
        $issueKey = $this->argument('issue');

        $headers = [];
        $tableRows = [];

        $wrap = intval($this->consoleWidth() * 75 / 100);
        $issue = app(Jira::class)->getIssue($issueKey);

        if(!$issue)
        {
            $this->error("Requested issue cannot be found");
            return;
        }

        $data = $issue->all()->only(['description','summary','assignee','reporter','status','issuetype'])->toArray();
        $this->drawLine();
        $this->comment($data['issuetype'] . ' ' . $issueKey . ' | ' . $data['status'] . ' | By: ' . $data['reporter'] .' | Assigned to: ' . $data['assignee']);
        $this->drawLine();
        $this->info($data['summary']);
        $this->line("");

        if(isset($data['description'])) 
        {
            $this->comment('Description:');
            $this->line($data['description']);
            $this->line("");
        }

        if($this->option('data') != 'no' || $this->output->isVerbose()) {

            $switch = $this->option('data');
            if($switch != 'v') {
                if($this->output->isVeryVerbose()) 
                {
                    $switch = 'v';
                }
            }

            switch($switch) {
            case 'v':
                $data = $issue->all()->forget(['description'])->toArray();
                foreach($data as $key => $value) {
                    if(!is_array($value)) 
                    {
                        $key = str_replace('_', ' ', $key);
                        $tableRows[] = [$key, $this->simplifyAndWrap($value, $wrap)];
                    }
                }

                break;
            default:
                $fields = [
                    'description' => 'Description',
                    'issuetype' => 'Type',
                    'project' => 'Project',
                    'created' => 'Created',
                    'labels' => 'Labels',
                    'assignee' => 'Assignee',
                    'status' => 'Status',
                    'components' => 'Components',
                    'creator' => 'Creator',
                    'reporter' => 'Reporter',
                    'duedate' => 'Due Date',
                    'key' => 'Issue Key',
                    'project_key' => 'Project Key',
                    'priority_name' => 'Priority',
                    'timetracking_timespentseconds' => 'Time Spent',
                ];

                $data = $issue->all()->only(array_keys($fields))->toArray();
                if(isset($data['description'])) 
                {
                    $data['description'] = $this->wrap($data['description'], $wrap, "\n", TRUE, "\r");
                }

                if(isset($data['timetracking_timespentseconds'])) 
                {
                    $data['timetracking_timespentseconds'] = $this->secToHM($data['timetracking_timespentseconds']);
                }

                foreach($fields as $key => $name) {
                    if(isset($data[$key])) 
                    {
                        $tableRows[] = [$name, $data[$key]];
                    }
                }
            }

            switch($this->option('style')) 
            {
            case 'csv':
                $this->csv($headers, $tableRows);
                break;
            case 'table':
                $this->comment('Data:');
                $this->table($headers, $tableRows);
                break;
            default:
            }
        }

        if($this->option('worklogs')) {
            $this->line("");
            $this->comment('Worklogs:');
            $this->call('jira:lsw', ['issue' => $issueKey, '--style' => $this->option('style')]);
        }

        if($this->option('comments')) {
            $this->line("");
            $this->comment('Comments:');
            $this->call('jira:lsc', ['issue' => $issueKey]);
        }
    }
}
