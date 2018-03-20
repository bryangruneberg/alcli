<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraCreateIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:ci {project : The project in which to create the issue} {--summary= : The summary string} {--description=} {--type=Task : The issue type} {--label=* : Include a label} {--component=* : Include a component} {--assign=} {--force}';
    protected $description = 'Create an issue in a project';

    public function handle()
    {
        $projectKey = $this->argument('project');

        $loggit = FALSE;
        if($this->option('force')) {
            if(!$this->option('summary')) 
            {
                $this->error('There is no summary');
                return;
            }

            if(!$this->option('type')) 
            {
                $this->error('There is no type');
                return;
            }

            $loggit = TRUE;
        }

        $description = $this->option('description');
        $summary = $this->option('summary');
        if(!$summary) 
        {
            $summary = $this->ask('Summary');
        }

        $type = $this->option('type');
        if(!$type) 
        {
            $type = $this->ask('Type');
        }

        while(!$loggit) {
            $this->drawLine();
            $this->info($projectKey);
            $this->line("Type: " . $type);
            $this->line("Assignee: " . $this->option('assign'));
            $this->line("Summary: " . $summary);
            $this->line("Description: " . $description);
            $this->line("Labels: " . implode(", ", $this->option('label')));
            $this->line("Components: " . implode(", ", $this->option('component')));
            $this->drawLine();
            $choice = $this->choice("Shall we create the issue?", ["Yes", "No", "Edit"]);
            switch($choice) {
            case "Yes":
                $loggit = TRUE;
                break;
            case "Edit":
                $type = $this->ask('Type', $type);
                $summary = $this->ask('Summary', $summary);
                $description = $this->ask('Description', $description);
                break;
            case "No":
            default:
            $this->error("Cancelled.");
            return;
            }
        }
        
        $iRaw = app(Jira::class)->createIssue($projectKey, $summary, $type, $description, $this->option('assign'), $this->option('label'), $this->option('component'));
        $iRes = $iRaw->getResult();
    
        if(!$iRes ||  !isset($iRes['key'])) 
        {
            $this->error("The new issue could not be created");
            if(is_array($iRes) || is_array($iRes['errors'])) {
                foreach($iRes['errors'] as $error) 
                {
                    $this->error($error);
                }
            }
            return;
        }

        $issue = app(Jira::class)->getIssue($iRes['key']);
        if(!$issue) {
            $this->error("The new issue could not be created");
            return;
        }

        $data = $issue->all()->only(['key','description','summary','assignee','reporter','status','issuetype'])->toArray();
        $this->drawLine();
        $this->comment($data['issuetype'] . ' ' . $data['key'] . ' | ' . $data['status'] . ' | By: ' . $data['reporter'] .' | Assigned to: ' . $data['assignee']);
        $this->drawLine();
        $this->info($data['summary']);
        $this->line("");
    }
}
