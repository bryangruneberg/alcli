<?php namespace App\Console\Commands;

use stdClass;
use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraGrabIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:grab {issue : The issue to view} {--user= : The user to assign the issue to}';
    protected $description = 'Assign JIRA issue to a user';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issue = app(Jira::class)->getIssue($issueKey);

        if(!$issue)
        {
            $this->error("Requested issue cannot be found");
            return;
        }

        $user = $this->getUsername($this->option('user'));
        if(!$user)
        {
            $currentUser = app(Jira::class)->getCurrentUserData();
            if(isset($currentUser['name']))
            {
                $user = $currentUser['name'];
            }
        }

        $data = $issue->all()->only(['assignee_name'])->toArray();
        $current_assignee = "";
        if(isset($data['assignee_name']))
        {
            $current_assignee = $data['assignee_name'];
            $this->info("Currently assigned to: " . $current_assignee);
        }  else {
            $this->info("Currently unassigned");
        }

        if($user == $current_assignee) 
        {
            $this->comment("No change needed");
            return;
        }

        try 
        {
            app(Jira::class)->editIssue($issueKey, NULL, ['assignee' => ['name' => $user]]);
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
            $this->info("Now assigned to: " . $data['assignee_name']);
        }  else {
            $this->info("Now unassigned");
        }
    }
}
