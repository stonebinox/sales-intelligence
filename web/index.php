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
    $string=addslashes(htmlentities($secure));
    return $string;
}
function validate($string)
{
    if(($string!="")&&($string!=NULL))
    {
        return true;
    }
    return false;
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
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
});
$app->get("/getEmails",function(Request $request) use($app){
    if($request->get("code"))
    {
        require("../classes/userMaster.php");
        require("../classes/emailMaster.php");
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
        $optParams['maxResults'] = 100; 
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
                echo $headers[$pos]->value.'<br>';
                //create user if not created
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
            echo $headers[$pos]->value.'<br>';

            $count=0;
            foreach($headers as $headerParts)
            {
                echo $count.') ';
                echo $headerParts->name.' - ';
                echo $headerParts->value;
                echo '<br>';
                $count+=1;
            }
            $parts = $content->getPayload()->getParts();
            $body = $parts[0]['body'];
            $rawData = $body->data;
            $sanitizedData = strtr($rawData,'-_', '+/');
            $decodedMessage = base64_decode($sanitizedData);
            $decodedMessage=secure($decodedMessage);
            echo '<br><br><br>';
            $mailCount+=1;
        }
        return "DONE";
    }
    else
    {
        return $app->redirect("/?err=AUTHENTICATION_ERROR");
    }
});
$app->run();
?>