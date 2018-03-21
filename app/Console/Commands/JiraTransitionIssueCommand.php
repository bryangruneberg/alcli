<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraTransitionIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:ti {issue : The issue to transition} {--state= : Transition state}';
    protected $description = 'Transition JIRA issue state';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issue = app(Jira::class)->getIssue($issueKey);
        $data = $issue->all()->only(['key','description','summary','assignee','reporter','status','issuetype'])->toArray();
        $this->drawLine();
        $this->comment($data['issuetype'] . ' ' . $data['key'] . ' | ' . $data['status'] . ' | By: ' . $data['reporter'] .' | Assigned to: ' . $data['assignee']);
        $this->drawLine();
        $this->info($data['summary']);
        $this->line("");

        $targetStates = app(Jira::class)->getTransitionStates($issueKey);

        $choices = [
            0 => 'Exit'
        ];
        foreach($targetStates as $targetState) {
            $choices[$targetState['id']] = $targetState['name'];
        }

        $state = $this->option("state");
        if($state) 
        {
            if(!in_array($state, $choices)) 
            {
                $this->error($state . " is not an acceptable state: " . implode(", ", $choices));
                return;
            }
        } else {
            $state = $this->choice("What state should we transition to?", $choices);
        }

        if($state == "Exit") 
        {
            $this->error("Cancelled.");
            return;
        }

        $stateId = array_search($state, $choices);
        $this->info("Changing state to " . $state . " [".$stateId."]");
        $result = app(Jira::class)->transitionIssue($issueKey, $stateId);

        $this->call('jira:cat', ['issue' => $issueKey]);
    }
}
