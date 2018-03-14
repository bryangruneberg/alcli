<?php namespace AmazeeLabs\alcli;

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class Jira
{

    protected $jiraUrl;
    protected $jiraUsername;
    protected $jiraPassword;

    protected $api;

    public function __construct($jiraUrl, $jiraUsername, $jiraPassword)
    {
        $this->jiraUrl = $jiraUrl;
        $this->jiraUsername = $jiraUsername;
        $this->jiraPassword = $jiraPassword;
    }

    public function getApi()
    {
        return new \chobie\Jira\Api(
          $this->jiraUrl,
          new \chobie\Jira\Api\Authentication\Basic($this->jiraUsername,
            $this->jiraPassword)
        );
    }

    public function getIssues($query) 
    {

      $api = $this->getApi();

      $walker = new Walker($api);
      $walker->push($query);
      
      $issues = [];
      foreach ($walker as $issue) {
          $issues[] = $issue;
      }

      return $issues;
    }

    public function getTempoWorklogs($user = NULL, $from = NULL, $to = NULL)
    {
      $params = [];
      if($user) { $params['username'] = $user; } 
      if($from) { $params['dateFrom'] = $from; } 
      if($to) { $params['dateTo'] = $to; } 

      $api = $this->getApi();
      return $api->api(Api::REQUEST_GET, '/rest/tempo-timesheets/3/worklogs', $params, true, false, false);
    }
}
