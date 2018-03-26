<?php namespace App;

use chobie\Jira\Issue as JiraIssue;

class Issue 
{
    private $jiraApi;
    private $jiraIssue;

    public function __construct(Jira $jiraApi) 
    {
        $this->jiraApi = $jiraApi;
    }  

    public function fill($issueKey, $expand = '', JiraIssue $prefetchedIssue = NULL) 
    {
        if($prefetchedIssue) 
        {
            $this->jiraIssue = $prefetchedIssue;
        } 
        else
        {
            $this->jiraIssue = $this->loadFromApi($issueKey, $expand);
        } 

        return $this;
    }

    public function loadFromApi($issueKey, $expand = '')
    {
        return new JiraIssue($this->jiraApi->getApi()->getIssue($issueKey, $expand)->getResult());
    }

    public function isEmpty() 
    {
        if(!$this->jiraIssue || empty($this->jiraIssue->getKey())) 
        {
            return TRUE;
        }

        return FALSE;
    }

    public function getLabels()
    {
        $data = array_change_key_case($this->jiraIssue->getFields(), CASE_LOWER);
        if(isset($data['labels'])) 
        {
            return $data['labels'];
        }

        return [];
    }

    public function all() 
    {
        $data = array_change_key_case(array_merge($this->jiraIssue->getFields(),['key' => $this->jiraIssue->getKey()]), CASE_LOWER);
        foreach($data as $k => $v) {
            if(is_array($v)) {
                $method = $this->create_flatten_method_name($k);
                if(method_exists($this, $method)) {
                    $this->$method($k, $v, $data);
                }
            }
        }
        return collect($data);
    }

    private function create_flatten_method_name($key, $prefix = 'flatten_') {
        $ret = preg_replace('/\W/',' ', $key);
        $ret = trim($ret);
        $ret = strtolower($ret);
        $ret = preg_replace('/\s+/','_', $ret);
        return $prefix . $ret;
    }

    public function flatten_worklog($key, $value, &$data) {
        $worklogRets = [];
        if(isset($value['worklogs']) && count($value['worklogs'])) {
            $worklogs = $value['worklogs'];
            foreach($worklogs as $wl) {
                $worklogRet = [];
                $k = 'worklog';
                $this->flatten_array($k, $wl, $worklogRet);
                $worklogRets[] = $worklogRet;
            }
        }

        $data['worklog'] = $worklogRets;
    }

    public function flatten_labels($key, $value, &$data) {
        $data[$key] = implode("," , $value);
    }

    public function flatten_components($key, $value, &$data) {
        $c = [];
        foreach($value as $component) {
            $c[] = $component['name'];
        }
        $data[$key] = implode("," , $c);
    }

    public function flatten_assignee($key, $value, &$data) {
        $data[$key] = $value['displayName'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_resolution($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }


    public function flatten_timetracking($key, $value, &$data) {
        $this->flatten_array($key, $value, $data);
        unset($data[$key]);
    }

    public function flatten_aggregateprogress($key, $value, &$data) {
        $this->flatten_array($key, $value, $data);
        unset($data[$key]);
    }

    public function flatten_project($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_votes($key, $value, &$data) {
        $data[$key] = $value['votes'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_progress($key, $value, &$data) {
        $data[$key] = $value['progress'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_reporter($key, $value, &$data) {
        $data[$key] = $value['displayName'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_creator($key, $value, &$data) {
        $data[$key] = $value['displayName'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_severity($key, $value, &$data) {
        $data[$key] = $value['value'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_issuetype($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_issue_type($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_status($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_account($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_priority($key, $value, &$data) {
        $data[$key] = $value['name'];

        $this->flatten_array($key, $value, $data);
    }

    public function flatten_array($key, $value, &$data) {
        foreach($value as $k => $v) {
            if(is_array($v)) {
                $this->flatten_array($key . '_' . $k, $v, $data);
            } else {
                $data[$key . '_' . strtolower($k)] = $v;
            }
        }
    }
}
