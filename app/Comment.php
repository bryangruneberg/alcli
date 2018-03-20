<?php namespace App;

class Comment 
{
    private $jiraApi;
    private $jiraComment;

    public function __construct(Jira $jiraApi) 
    {
        $this->jiraApi = $jiraApi;
    }  

    public function fill($issueKey, array $prefetchedComment) 
    {
        $this->jiraComment = $prefetchedComment;
        return $this;
    }

    public function all() 
    {
        $data = array_change_key_case($this->jiraComment, CASE_LOWER);
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

    public function flatten_author($key, $value, &$data) 
    {
        $data[$key] = $value['displayName'];
        $this->flatten_array($key, $value, $data);
    }


    public function flatten_updateauthor($key, $value, &$data) 
    {
        $data[$key] = $value['displayName'];
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
