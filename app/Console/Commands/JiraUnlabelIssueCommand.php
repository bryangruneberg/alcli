<?php namespace App\Console\Commands;

use stdClass;
use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraUnlabelIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:unlabel {issue : The issue to unlabel} {label}';
    protected $description = 'Remove a label from a JIRA issue';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issue = app(Jira::class)->getIssue($issueKey);

        if(!$issue)
        {
            $this->error("Requested issue cannot be found");
            return;
        }

        $labels = $issue->getLabels();
        $removeLabel = strtolower($this->argument("label"));
        if(!in_array($removeLabel, $labels)) 
        {
            $this->info("Already not labeled with " . $removeLabel);
            return;
        }

        $this->info('Current labels: ' . implode(", ", $labels));

        $labels = array_diff( $labels, [$removeLabel] );

        try 
        {
            app(Jira::class)->editIssue($issueKey, NULL, ['labels' => $labels]);
        } 
        catch(\Exception $ex) 
        {
            $this->error($ex->getMessage());
            return;
        }

        $issue = app(Jira::class)->getIssue($issueKey);
        $labels = $issue->getLabels();
        $this->info('Now labels: ' . implode(", ", $labels));
    }
}
