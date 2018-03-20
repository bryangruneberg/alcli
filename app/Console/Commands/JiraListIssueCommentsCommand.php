<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraListIssueCommentsCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:lsc {issue : The issue to list}';
    protected $description = 'List JIRA issue comments';

    public function handle()
    {
        $issueKey = $this->argument('issue');

        $comments = app(Jira::class)->getIssueComments($issueKey);

        if(count($comments) <= 0) 
        {
            $this->error("No comments found");
            return;
        }

        $totalSecWorked = 0;
        foreach($comments as $comment) {
            $data = $comment->all()->only(['created', 'author', 'body'])->toArray();

            $commentString = $this->simplifyAndWrap($data['body']);

            $dt = new \DateTime($data['created']);

            $this->line("");
            $this->drawLine();
            $this->comment($data['author'] . ' | ' . $dt->format('Y-m-d H:i'));
            $this->line("");
            $this->line($data['body']);

        }
    }
}
