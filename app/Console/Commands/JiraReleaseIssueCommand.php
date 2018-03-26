<?php namespace App\Console\Commands;

use stdClass;
use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraReleaseIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:release {issue : The issue to view}';
    protected $description = 'Unassign JIRA issue';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issue = app(Jira::class)->getIssue($issueKey);

        if(!$issue)
        {
            $this->error("Requested issue cannot be found");
            return;
        }

        $data = $issue->all()->only(['assignee_name'])->toArray();
        $current_assignee = "";
        if(isset($data['assignee_name']))
        {
            $current_assignee = $data['assignee_name'];
            $this->info("Currently assigned to: " . $current_assignee);
        }  else {
            $this->info("Currently unassigned");
            return;
        }


        try 
        {
            app(Jira::class)->editIssue($issueKey, NULL, ['assignee' => ['name' => '']]);
        } 
        catch(\Exception $ex) 
        {
            $this->error($ex->getMessage());
            return;
        }

        $issue = app(Jira::class)->getIssue($issueKey);
        $data = $issue->all()->only(['assignee_name'])->toArray();
        if(isset($data['assignee_name']))
        {
            $this->info("Still assigned to: " . $data['assignee_name']);
        }  else {
            $this->info("Now unassigned");
        }
    }
}
