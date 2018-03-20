<?php namespace App\Console\Commands;

use App\Jira;
use App\Comment;
use App\Issue;
use Illuminate\Console\Command;

class JiraCommentIssueCommand extends JiraBaseCommand 
{
    protected $signature = 'jira:pc {issue : The issue on which to comment} {--comment= : The comment string} {--list} {--force}';
    protected $description = 'Post a comment on a JIRA issue';

    public function handle()
    {
        $issueKey = $this->argument('issue');
        $issueKey = $this->getIssueKey($issueKey);
        $issue = app(Jira::class)->getIssue($issueKey);

        if($this->option('list')) 
        {
            $config = app(Jira::class)->resolveConfigArray();
            if(isset($config['issues'])) 
            {
                $issues = $config['issues'];
                $tableRows = [];
                foreach($issues as $k => $v) 
                {
                    $tableRows[] = array($k, $v);
                }

                $this->table(['Shortcut','Issue Key'], $tableRows);
            }
            return;
        }

        if(!$issue)
        {
            $this->error('The requested issue ' . $issueKey . ' cannot be loaded');
            return;
        }

        $loggit = FALSE;
        if($this->option('force')) {
            if(!$this->option('comment')) 
            {
                $this->error('There is no comment');
                return;
            }

            $loggit = TRUE;
        }

        $comment = $this->option('comment');
        if(!$comment) 
        {
            $comment = $this->ask('Comment');
        }

        while(!$loggit) {
            $this->drawLine();
            $this->info($issueKey);
            $this->line("Comment: " . $comment);
            $this->drawLine();
            $choice = $this->choice("Shall we post the comment?", ["Yes", "No", "Edit"]);
            switch($choice) {
            case "Yes":
                $loggit = TRUE;
                break;
            case "Edit":
                $comment = $this->ask('Comment', $comment);
                break;
            case "No":
            default:
            $this->error("Cancelled.");
            return;
            }
        }
        
        $icRes = app(Jira::class)->addComment($issueKey, $comment);
        $icRaw = $icRes->getResult();

        $ic = app(Comment::class);
        $ic->fill($issueKey, $icRaw);
        $data = $ic->all()->only(['author','created','body'])->toArray();

        $this->table([], [['Author',$data['author']], ['Date', $data['created']], ['Comment', $data['body']]]);
    }
}
