<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraListIssuesCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:ls {query=mine : query or stored query name} {--style=table : table or csv output}';
    protected $description = 'List JIRA issues';

    public function handle()
    {
        $query = $this->argument('query');
        $queryString = $this->getIssueQuery($query);
        if(!$queryString) 
        {
            $queryString = $query;
            $this->info("Your query could not be looked up, assuming raw JQL.");
        }

        $issues = app(Jira::class)->getIssues($queryString);
        if(!$issues || count($issues) <= 0) 
        {
            $this->error("No issues match your query");
            return;
        }

        if($this->output->isVeryVerbose()) 
        {
            $headers = ['Key', 'Type', 'Status', 'Summary', 'Assignee', 'Priority', 'Due', 'Labels'];
        } else if($this->output->isVerbose()) {
            $headers = ['Key', 'Status', 'Summary', 'Assignee', 'Due', 'Labels'];
        } else {
            $headers = ['Key', 'Summary', 'Assignee', 'Due', 'Labels'];
        }

        $rows = [];

        foreach($issues as $issue) 
        {
            $issueData = $issue->all()->only(['status', 'key','labels','issue type_name','assignee','summary','priority_name','due date'])->toArray();


            if($this->output->isVeryVerbose()) {
                $wrap = intval($this->consoleWidth() / 5);
                $rows[] = [
                    $issueData['key'],
                    $issueData['issue type_name'],
                    $issueData['status'],
                    $this->wrap($issueData['summary'], $wrap),
                    $issueData['assignee'],
                    $issueData['priority_name'] ?? "",
                    $issueData['due date'] ?? "",
                    str_replace(",", "\n", $issueData['labels'])
                ];
            } else if($this->output->isVerbose()) {
                $wrap = intval($this->consoleWidth() / 4);
                $whos = explode(" ", $issueData['assignee']);
                $rows[] = [
                    $issueData['key'],
                    $issueData['status'],
                    $this->wrap($issueData['summary'], $wrap),
                    $whos[0] ?? "",
                    $issueData['due date'] ?? "",
                    str_replace(",", "\n", $issueData['labels'])
                ];
            } else {
                $wrap = intval($this->consoleWidth() / 3);
                $summary = $issueData['summary'];
                $whos = explode(" ", $issueData['assignee']);
                $rows[] = [
                    $issueData['key'],
                    $this->wrap($summary, $wrap),
                    $whos[0] ?? "",
                    $issueData['due date'] ?? "",
                    str_replace(",", "\n", $issueData['labels'])
                ];
            }
        }

        switch($this->option('style')) 
        {
        case 'csv':
            $this->csv($headers, $rows);
            break;
        case 'table':
            $this->table($headers, $rows);
            break;
        default:
        }
    }
}
