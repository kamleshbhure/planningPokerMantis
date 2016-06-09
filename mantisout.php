<?php
header('Access-Control-Allow-Headers: X-Requested-With,Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Origin: *');
define('DOCUMENT_ROOT', $_SERVER["DOCUMENT_ROOT"]);

require_once DOCUMENT_ROOT . '/models/Bootstrap.php';
$bootstrap = new Bootstrap();
$bootstrap->setup('zf', 'library-1.6.2');

require_once 'Mappers.php';

$orm = Vodafone_Orm::getInstance();


$mantisServer = 'bugtracker.internal.tnf.nl';
$username = 'ticketreporter';
$password = 'JLKS872@';
$masterPassword = 'm@nt1$';
$method = $_GET['method'];
$client = new SoapClient('http://'.$mantisServer.'/api/soap/mantisconnect.php?wsdl');

switch ($method) {
    case 'getProjects' :
        $projects = $client->mc_projects_get_user_accessible($username, $password);
        $result = array();
        foreach ($projects[0]->subprojects as $subproject) {
            $tmp = new stdClass();
            $tmp->id = $subproject->id;
            $tmp->name = $subproject->name;
            $result[] = $tmp;
        }
        echo Zend_Json::encode($result);
        break;
    case 'getIssuesByProjectId' :
        $issues = $client->mc_project_get_issues($username, $password, 117);
        $result = array();
        foreach ($issues as $issue) {
            if ($issue->status->name == 'new' || $issue->status->name == 'feedback') {
                $tmp = new stdClass();
                $tmp->id = $issue->id;
                $tmp->summary = $issue->id." - ".$issue->summary;
                $result[] = $tmp;
            }
        }
        echo Zend_Json::encode($result);
        break;
    case 'updateUserStory' :
        $_POST = Zend_Json::decode(file_get_contents('php://input'), true);
        $id = $_POST['id'];
        $storytPoints = $_POST['points'];
        $estimatedTime = $_POST['estimatedTime'];

        $result = array();
        $result['success'] = false;
        echo Zend_Json::encode($result);
        die;

        try {
            $issue = $client->mc_issue_get($username, $password, $id);
            foreach ($issue->custom_fields as $key => $customField) {
                switch ($customField->field->name) {
                    case 'Est.Work' :
                        if ($estimatedTime) {
                            $issue->custom_fields[$key]->value = $estimatedTime;
                        }
                        break;
                    case 'Rem.Work' :
                        if ($estimatedTime) {
                            $issue->custom_fields[$key]->value = $estimatedTime;
                        }
                        break;
                    case 'Story Points' :
                        if ($storytPoints) {
                            $issue->custom_fields[$key]->value = $storytPoints;
                        }
                        break;
                }
            }
            $updateResponse = $client->mc_issue_update($username, $password, $id, $issue);
            if ($updateResponse) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }
        } catch (Exception $e){
            $result['success'] = false;
        }
        echo Zend_Json::encode($result);
        break;
    case 'verifyPassword' :
        $_POST = Zend_Json::decode(file_get_contents('php://input'), true);
        $password = $_POST['password'];
        $result = array();
        if ($password == $masterPassword) {
            $result['success'] = true;
        } else {
            $result['success'] = false;
        }
        echo Zend_Json::encode($result);
        break;
}