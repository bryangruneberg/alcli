<?php namespace App\Console\Commands;

use App\Jira;
use App\Issue;
use Illuminate\Console\Command;

class JiraBaseCommand extends Command 
{
    public function getIssueQuery($queryName, $groupName = 'alcli')
    {
        return app(Jira::class)->getIssueQuery($queryName, $groupName);
    }

    public function getIssueKey($queryKey, $groupName = 'alcli')
    {
        $key = app(Jira::class)->getIssueKey($queryKey, $groupName);
        if($key) 
        {
            return $key;
        }

        return $queryKey;
    }


    public function getUsername($userName, $groupName = 'alcli')
    {
        $un = app(Jira::class)->getUsername($userName, $groupName);
        if($un) 
        {   
            return $un;
        }

        return $userName;
    }

    public function csv(array $headers, array $rows) 
    {
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach($rows as $row) { 
            foreach($row as $k => $v) {
                $row[$k] = str_replace("\r", " ", str_replace("\n"," ", $v));
            }
            fputcsv($output, $row);
        }
    }

    public function secToHM($sec) {
      $hours = floor($sec / 3600);
      $minutes = floor(($sec / 60) % 60);
      $seconds = $sec % 60;

      return $hours . "h ". $minutes ."m";
    }

    public function consoleWidth() {
        $width = exec('tput cols');
        return $width;
    }

    public function simplifyAndWrap($string, $width = NULL, $break = "\n", $cut = TRUE) 
    {
        $ret = str_replace("\n"," ", $string);
        $ret = str_replace("\r"," ", $ret);

        return $this->wrap($ret, $width, $break, $cut);
    }

    public function wrap($string, $width = NULL, $break = "\n", $cut = TRUE, $filter = NULL) 
    {
        if(!$width) 
        {
            $width = intval($this->consoleWidth() / 4);
        }

        if($filter)
        {
          $ret = str_replace($filter," ", $string);
        }
        else 
        {
            $ret = $string;
        }

        return wordwrap($ret, $width, $break, $cut);
    }

    public function drawLine($length = NULL, $char = '-')
    {
        if(!$length) 
        {
            $length = intval($this->consoleWidth() * 2 / 3);
        }

        $line = $char;
        for($i =0; $i <= $length; $i++) 
        {
            $line .= $char;
        }

        $this->line($line);
    }
}
