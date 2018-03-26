<?php namespace App;

use stdClass;
use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Yaml\Yaml;

class Jira
{
	const ALCLI_YML_EXT = '.yml';

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

	public function getTransitionStates($issueKey) 
	{
		// Get available transitions.
		$api = $this->getApi();
		$tmp_transitions = $api->getTransitions($issueKey, array());
		$tmp_transitions_result = $tmp_transitions->getResult();
		$transitions = $tmp_transitions_result['transitions'];
		return $transitions;
	}

	public function editIssue($issueKey, stdClass $object = NULL, array $fields = [])
	{
		$api = $this->getApi();
		$updates = [];
		if($object) 
		{
			$updates['update'] = $object;
		}

		if(count($fields)) 
		{
			$updates['fields'] = $fields;
		}

        $r = $api->editIssue($issueKey, $updates);
        if(is_object($r))
        {
            $res = $r->getResult();
            if(isset($res['errors'])) 
            {
                $error = "";
                foreach($res['errors'] as $k => $v) 
                {
                   $error .= $k .": " . $v. " ";
                }

                throw new \Exception($error);
            }
        }

        return $r;
	}

	public function transitionIssue($issueKey, $targetState)
	{
		$api = $this->getApi();
		$result = $api->transition(
			$issueKey,
			array(
				'transition' => array('id' => $targetState),
			)
		);

		return($result);
	}

	public function getIssueWorklogs($issue) 
	{
		$api = $this->getApi();
		$result = $api->getWorklogs($issue, [])->getResult();

		if(!isset($result['worklogs'])) {
			return [];
		}

		$rawWorkLogs = $result['worklogs'];
		$workLogs = [];

		foreach($rawWorkLogs as $wl) 
		{
			$workLog = app(Worklog::class);
			$workLog->fill($issue, $wl);

			$workLogs[] = $workLog;
		}

		return $workLogs;
	}

	public function getIssueComments($issueKey) 
	{
		$comments = [];

		$issue = $this->getIssue($issueKey, '');
		$rawData = $issue->all()->only(['comment'])->toArray();
		if(!isset($rawData['comment']) || !isset($rawData['comment']['comments'])) 
		{
			return [];
		}

		$rawComments = $rawData['comment'];

		foreach($rawComments['comments'] as $cm) 
		{
			$comment = app(Comment::class);
			$comment->fill($issueKey, $cm);

			$comments[] = $comment;
		}

		return $comments;
	}

	public function getIssues($query) 
	{

		$api = $this->getApi();

		$walker = new Walker($api);
		$walker->push($query);

		$issues = [];
		foreach ($walker as $jiraIssue) {
			$issue = app(Issue::class);
			$issue->fill($jiraIssue->getKey(), '', $jiraIssue);
			$issues[] = $issue;
		}

		return $issues;
	}

	public function getIssue($issueKey) 
	{
		$issue = app(Issue::class);
		$issue->fill($issueKey, '');
		if(!$issue || $issue->isEmpty()) 
		{
			return NULL;
		}

		return $issue;
	}

	public function getCurrentUserData()
	{
		$api = $this->getApi();
		$rawUser = $api->api(Api::REQUEST_GET, '/rest/auth/latest/session')->getResult();

		if(isset($rawWorkLogs['errorMessages'])) 
		{
			return [];
		}

		return $rawUser;
	}

	public function getTempoUserWorklogs($user = NULL, $from = NULL, $to = NULL)
	{
		$params = [];
		if($user) { $params['username'] = $user; } 
		if($from) { $params['dateFrom'] = $from; } 
		if($to) { $params['dateTo'] = $to; } 

		$api = $this->getApi();
		$rawWorkLogs = $api->api(Api::REQUEST_GET, '/rest/tempo-timesheets/3/worklogs', $params, true, false, false);

		if(isset($rawWorkLogs['errorMessages'])) 
		{
			return [];
		}

		$workLogs = [];

		foreach($rawWorkLogs as $wl) 
		{
			$issue = $wl['issue']['key'];

			$workLog = app(Worklog::class);
			$workLog->fill($issue, $wl);

			$workLogs[] = $workLog;
		}

		return $workLogs;
	}

	public function addComment($issue, $comment)
	{
		$params = [];
		if($comment) { $params['body'] = $comment; } 

		$api = $this->getApi();
		$ret = $api->api(Api::REQUEST_POST, '/rest/api/2/issue/'.$issue.'/comment', $params);

		return $ret;
	}

	public function addWorklog($issue, $timeSpent, $date, $comment)
	{
		$params = [];
		if($issue) { $params['issueId'] = $issue; } 
		if($date) { $params['started'] = $date; } 
		if($timeSpent) { $params['timeSpent'] = $timeSpent; } 
		if($comment) { $params['comment'] = $comment; } 

		$api = $this->getApi();
		return $api->api(Api::REQUEST_POST, '/rest/api/2/issue/'.$issue.'/worklog', $params);
	}

	public function getIssueQuery($queryName)
	{
		$configArray = self::resolveConfigArray();
		if(isset($configArray['queries']) && isset($configArray['queries'][$queryName])) 
		{
			return $configArray['queries'][$queryName];
		}

		return NULL;
	}

	public function getUsername($userName)
	{
		$configArray = self::resolveConfigArray();
		if(isset($configArray['users']) && isset($configArray['users'][$userName])) 
		{
			return $configArray['users'][$userName];
		}

		return NULL;
	}

	public function getIssueKey($queryKey)
	{
		$configArray = self::resolveConfigArray();
		if(isset($configArray['issues']) && isset($configArray['issues'][$queryKey])) 
		{
			return $configArray['issues'][$queryKey];
		}

		return NULL;
	}

	public function createIssue($projectKey, $summary, $type, $description = NULL, $assign = NULL, array $labels, array $components) 
	{
		$api = $this->getApi();

		$params = [
			'fields' => [
				'project' => [
					'key' => $projectKey
				],
				'summary' => $summary,
				'issuetype' => [
					"name" => $type
				]
			]
		];

		if($description) 
		{
			$params['fields']['description'] = $description;
		}

		if($assign) 
		{
			$params['fields']['assignee']['name'] = $assign;
		}

		if(count($labels)) 
		{
			$params['fields']['labels'] = $labels;
		}

		if(count($components)) 
		{
			$params['fields']['components'] = [];
			foreach($components as $component) 
			{
				$params['fields']['components'][] = [
					'name' => $component
				];
			}
		}

		return $api->api(Api::REQUEST_POST, '/rest/api/2/issue/', $params);
	}

	public static function resolveConfigArray($fileName = NULL) 
	{

		if(!$fileName) {
			$fileName = (app()->environment()).'_alcli';
		}

		$config = [];
		$found = FALSE;

		if(file_exists(getcwd() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)) {
			$config = self::array_merge_recursive_distinct($config, Yaml::parse(file_get_contents(getcwd() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)));
			$config['ymlfiles'][] = getcwd() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT;
			$found = TRUE;
		}

		if(file_exists(self::getHomeDirectory() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)) {
			$config = self::array_merge_recursive_distinct($config, Yaml::parse(file_get_contents( self::getHomeDirectory() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)));
			$config['ymlfiles'][] = self::getHomeDirectory() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT;
			$found = TRUE;
		}


		if(file_exists(base_path() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)) {
			$config = self::array_merge_recursive_distinct($config, Yaml::parse(file_get_contents(base_path() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT)));
			$config['ymlfiles'][] = base_path() . DIRECTORY_SEPARATOR . $fileName . self::ALCLI_YML_EXT;
			$found = TRUE;
		}

		if(!$found && $fileName != 'alcli') {
			return self::resolveConfigArray('alcli');
		}

		return $config;
	}

	public static function getHomeDirectory()
	{
		return $_SERVER['HOME'];
	}

	/**
	 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
	 * this happens (documented behavior):
	 *
	 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('org value', 'new value'));
	 *
	 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array, as is the
	 * case with array_merge, i.e.:
	 *
	 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('new value'));
	 *
	 * Parameters are passed by reference, though only for performance reasons. They're not
	 * altered by this function.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	public static function array_merge_recursive_distinct ( array $array1, array $array2 )
	{
		$merged = $array1;

		foreach ( $array2 as $key => &$value )
		{
			if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
			{
				$merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
			}
			else
			{
				$merged [$key] = $value;
			}
		}

		return $merged;
	}

}
