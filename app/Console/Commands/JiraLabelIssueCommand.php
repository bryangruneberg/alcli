<?php namespace App\Console\Commands;

use stdClass;
use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraLabelIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:label {issue : The issue to label} {label}';
    protected $description = 'Assign a label to a JIRA issue';

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
        $newLabel = strtolower($this->argument("label"));
        if(in_array($newLabel, $labels)) 
        {
            $this->info("Already labeled with " . $newLabel);
            return;
        }

        $this->info('Current labels: ' . implode(", ", $labels));

        $labels[] = $newLabel;

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
