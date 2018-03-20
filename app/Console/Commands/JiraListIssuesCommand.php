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

        $headers = ['Key', 'Type', 'Status', 'Summary', 'Assignee', 'Labels'];
        $rows = [];

        foreach($issues as $issue) 
        {
            $issueData = $issue->all()->only(['status', 'key','labels','issue type_name','assignee','summary'])->toArray();

            $wrap = intval($this->consoleWidth() / 2);
            $rows[] = [
                $issueData['key'],
                $issueData['issue type_name'],
                $issueData['status'],
                $this->wrap($issueData['summary'], $wrap),
                $issueData['assignee'],
                str_replace(",", "\n", $issueData['labels'])
            ];
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
