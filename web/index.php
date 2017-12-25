<?php

ini_set('display_errors', 1);
require_once __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/prod.php';
require __DIR__.'/../src/controllers.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
function secure($string)
{
    $string=addslashes(htmlentities($string));
    return $string;
}
function validate($string)
{
    if(($string!="")&&($string!=NULL))
    {
        return true;
    }
    else
    {
        return false;
    }
}
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
      'driver' => 'pdo_mysql',
      'dbname' => 'heroku_bede2add7766338',
      'user' => 'b496bdcb31bc8c',
      'password' => '83ba04a2',
      'host'=> "us-cdbr-iron-east-05.cleardb.net",
    )
));
$app->register(new Silex\Provider\SessionServiceProvider, array(
    'session.storage.save_path' => dirname(__DIR__) . '/tmp/sessions'
));
$app->before(function(Request $request) use($app){
    $request->getSession()->start();
});
$app->get("/",function() use($app){
    return $app['twig']->render("index.html.twig");
});
$app->get("/auth",function() use($app){
    $client = new Google_Client();
    $client->setAuthConfig('client_secret.json');
    $client->setAccessType("offline");        // offline access
    $client->setIncludeGrantedScopes(true);   // incremental auth
    $client->setDeveloperKey("AIzaSyDHDuBK9PYzXHk_0EMeZy4FdgZd32_Rq1U");
    $client->addScope("https://www.googleapis.com/auth/gmail.readonly");
    $auth_url = $client->createAuthUrl();
    // return header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    return $app->redirect($auth_url);
});
$app->get("/getEmails",function(Request $request) use($app){
    if($request->get("code"))
    {
        require("../classes/userMaster.php");
        require("../classes/emailMaster.php");
        $userObj=new emailMaster;
        $client = new Google_Client();
        $client->setAuthConfig('client_secret.json');
        $client->setAccessType("offline");        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $client->setDeveloperKey("AIzaSyDHDuBK9PYzXHk_0EMeZy4FdgZd32_Rq1U");
        $client->authenticate($request->get("code"));
        $access_token = $client->getAccessToken();
        $service = new Google_Service_Gmail($client);
        $user = 'me';
        $optParams = [];
        $optParams['maxResults'] = 500; 
        $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $mailCount=0;
        foreach($list as $listItem)
        {
            $messageID=$listItem->getId();
            $optParamsGet = [];
            $optParamsGet['format'] = 'full'; // Display message in payload
            $content=$service->users_messages->get('me',$messageID, $optParamsGet);
            $messagePayload = $content->getPayload();
            $headers = $messagePayload->getHeaders();
            if($mailCount==0)
            {
                $pos=NULL;
                for($i=0;$i<count($headers);$i++)
                {
                    $headerParts=$headers[$i];
                    if($headerParts->name=="Delivered-To")
                    {
                        $pos=$i;
                        break;
                    }
                }
                $emailID=$headers[$pos]->value;
                $response=$userObj->addUser($emailID);
                if(strpos($response,"USER_AUTHENTICATED_")===false)
                {
                    return $app->redirect("/?err=AUTHENTICATION_FAILURE");                    
                }
                $e=explode("USER_AUTHENTICATED_",$response);
                $userID=$e[1];
            }
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="From")
                {
                    $pos=$i;
                    break;
                }
            }
            $from=$headers[$pos]->value;
            if(strpos($from,'<')!==false)
            {
                $rev=strrev($from);
                $e=explode(" ",$rev);
                $from=trim(strrev($e[0]));
                $from=ltrim($from,'<');
                $from=rtrim($from,'>');
                $pieces=[];
                for($i=1;$i<count($e);$i++)
                {
                    array_push($pieces,$e[$i]);
                }
                $emailerName=trim(strrev(implode(" ",$pieces)));
                // $emailerName=trim(strrev($e[1]));
            }
            else
            {
                $emailerName=$from;
            }
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="Subject")
                {
                    $pos=$i;
                    break;
                }
            }
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="Date")
                {
                    $pos=$i;
                    break;
                }
            }
            $date=$headers[$pos]->value;
            $subject=$headers[$pos]->value;
            $parts = $content->getPayload()->getParts();
            $body = $parts[0]['body'];
            $rawData = $body->data;
            $sanitizedData = strtr($rawData,'-_', '+/');
            $decodedMessage = base64_decode($sanitizedData);
            $decodedMessage=secure($decodedMessage);
            $emailResponse=$userObj->addEmail($userID,$from,$subject,$decodedMessage,'Inbox',$emailerName,$date);
            $mailCount+=1;
        }
        $user = 'me';
        $optParams = [];
        $optParams['maxResults'] = 500; 
        $optParams['labelIds'] = 'SENT'; // Only show messages in Sent
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $mailCount=0;
        foreach($list as $listItem)
        {
            $messageID=$listItem->getId();
            $optParamsGet = [];
            $optParamsGet['format'] = 'full'; // Display message in payload
            $content=$service->users_messages->get('me',$messageID, $optParamsGet);
            $messagePayload = $content->getPayload();
            $headers = $messagePayload->getHeaders();
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="To")
                {
                    $pos=$i;
                    break;
                }
            }
            $to=$headers[$pos]->value;
            if(strpos($to,'<')!==false)
            {
                $rev=strrev($to);
                $e=explode(" ",$rev);
                $to=trim(strrev($e[0]));
                $to=ltrim($to,'<');
                $to=rtrim($to,'>');
                // $emailerName=trim(strrev($e[1]));
                $pieces=[];
                for($i=1;$i<count($e);$i++)
                {
                    array_push($pieces,$e[$i]);
                }
                $emailerName=trim(strrev(implode(" ",$pieces)));
            }
            else
            {
                $emailerName=$to;
            }
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="Subject")
                {
                    $pos=$i;
                    break;
                }
            }
            $subject=$headers[$pos]->value;
            $pos=NULL;
            for($i=0;$i<count($headers);$i++)
            {
                $headerParts=$headers[$i];
                if($headerParts->name=="Date")
                {
                    $pos=$i;
                    break;
                }
            }
            $date=$headers[$pos]->value;
            // $count=0;
            // foreach($headers as $headerParts)
            // {
            //     echo $count.') ';
            //     echo $headerParts->name.' - ';
            //     echo $headerParts->value;
            //     echo '<br>';
            //     $count+=1;
            // }
            $parts = $content->getPayload()->getParts();
            $body = $parts[0]['body'];
            $rawData = $body->data;
            $sanitizedData = strtr($rawData,'-_', '+/');
            $decodedMessage = base64_decode($sanitizedData);
            $decodedMessage=secure($decodedMessage);
            $emailResponse=$userObj->addEmail($userID,$to,$subject,$decodedMessage,'Sent',$emailerName,$date);
            $mailCount+=1;
        }
        return $app->redirect("/dashboard");
        // return "DONE";
    }
    else
    {
        return $app->redirect("/?err=AUTHENTICATION_ERROR");
    }
});
$app->get("/dashboard",function() use($app){
    if($app['session']->get("uid"))
    {
        return $app['twig']->render("dashboard.html.twig");
    }
    else
    {
        return $app->redirect("/");
    }
});
$app->get("/emails",function() use($app){
    if($app['session']->get("uid"))
    {
        require("../classes/userMaster.php");
        require("../classes/emailMaster.php");
        $email=new emailMaster;
        $emails=$email->getEmails($app['session']->get("uid"));
        if(is_array($emails))
        {
            return json_encode($emails);
        }
        return $emails;
    }
    else
    {
        return "INVALID_PARAMETERS";
    }
});
$app->get("/logout",function() use($app){
    if($app['session']->get("uid"))
    {
        $app['session']->remove("uid");
        return $app->redirect("/");
    }
    else
    {
        return $app->redirect("/");
    }
});
$app->get("/getAuthStatus",function() use($app){
    if($app['session']->get("uid")){
        return "USER_AUTHORIZED" ;
    }
    else
    {
        return "USER_NOT_AUTHORIZED";
    }
});
$app->run();
?>