<?php
/*----------------------------
Author: Anoop Santhanam
Date Created: 24/12/17 11:33
Last modified: 24/12/17 11:33
Comments: Main class file for
email_master table.
----------------------------*/
class emailMaster extends userMaster
{
    public $app=NULL;
    public $emailValid=false;
    private $email_id=NULL;
    function __construct($emailID=NULL)
    {
        $this->app=$GLOBALS['app'];
        if(validate($emailID))
        {
            $this->email_id=secure($emailID);
            $this->emailValid=$this->verifyEmail();
        }
    }
    function verifyEmail()
    {
        if(validate($this->email_id))
        {
            $app=$this->app;
            $emailID=$this->email_id;
            $em="SELECT user_master_iduser_master FROM email_master WHERE stat='1' AND idemail_master='$emailID'";
            $em=$app['db']->fetchAssoc($em);
            if(validate($em))
            {
                $userID=$em['user_master_iduser_master'];
                userMaster::__construct($userID);
                if($this->userValid)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    function getEmail()
    {
        if($this->emailValid)
        {
            $app=$this->app;
            $emailID=$this->email_id;
            $em="SELECT * FROM email_master WHERE idemail_master='$emailID'";
            $em=$app['db']->fetchAssoc($em);
            if(validate($em))
            {
                $userID=$em['user_master_iduser_master'];
                userMaster::__construct($userID);
                $user=userMaster::getUser();
                if(is_array($user))
                {
                    $em['user_master_iduser_master']=$user;
                }
                return $em;
            }
            else
            {
                return "INVALID_EMAIL_ID";
            }
        }
        else
        {
            return "INVALID_EMAIL_ID";
        }
    }
    function getEmails($userID,$offset=0)
    {
        $userID=secure($userID);
        userMaster::__construct($userID);
        if($this->userValid)
        {
            $app=$this->app;
            $em="SELECT idemail_master FROM email_master WHERE stat='1' AND user_master_iduser_master='$userID' ORDER BY idemail_master DESC LIMIT $offset,500";
            $em=$app['db']->fetchAll($em);
            $emailArray=array();
            foreach($em as $email)
            {
                $emailID=$email['idemail_master'];
                $this->__construct($emailID);
                $emailData=$this->getEmail();
                if(is_array($emailData))
                {
                    array_push($emailArray,$emailData);
                }
            }
            if(count($emailArray)>0)
            {
                return $emailArray;
            }
            else
            {
                return "NO_EMAILS_FOUND";
            }
        }
        else
        {
            return "INVALID_USER_ID";
        }
    }
    function addEmail($userID,$from,$subject,$body,$mailbox='Inbox',$emailerName='',$emailDate='')
    {
        $userID=secure($userID);
        userMaster::__construct($userID);
        if($this->userValid)
        {
            $app=$this->app;
            $from=secure($from);
            if(validate($from))
            {
                $subject=trim(secure($subject));
                $body=trim(secure($body));
                $mailbox=secure($mailbox);
                $emailerName=secure($emailerName);
                $emailDate=secure($emailDate);
                $em="SELECT idemail_master FROM email_master WHERE stat='1' AND user_master_iduser_master='$userID' AND from_email='$from' AND email_subject='$subject' AND email_mailbox='$mailbox'";
                $em=$app['db']->fetchAssoc($em);
                if(!validate($em))
                {
                    $in="INSERT INTO email_master (timestamp,user_master_iduser_master,from_email,email_subject,email_body,email_mailbox,email_from_name,email_date) VALUES (NOW(),'$userID','$from','$subject','$body','$mailbox','$emailerName','$emailDate')";
                    $in=$app['db']->executeQuery($in);
                    return "EMAIL_ADDED";
                }
                else
                {
                    return "EMAIL_ALREADY_ADDED";
                }
            }
            else
            {
                return "INVALID_FROM";
            }
        }
        else
        {
            return "INVALID_USER_ID";
        }
    }
    function getEmailUser()
    {
        if($this->emailValid)
        {
            $app=$this->app;
            $emailID=$this->email_id;
            $em="SELECT user_master_iduser_master FROM email_master WHERE idemail_master='$emailID'";
            $em=$app['db']->fetchAssoc($em);
            if(validate($em))
            {
                return $em['user_master_iduser_master'];
            }
            else
            {
                return "INVALID_EMAIL_ID";
            }
        }
        else
        {
            return "INVALID_EMAIL_ID";
        }
    }
    function sendEmail($message)
    {
        if($this->emailValid)
        {
            $app=$this->app;
            $emailID=$this->email_id;
            $userID=$this->getEmailUser();
            userMaster::__construct($userID);
            $userEmail=userMaster::getEmailID();
            $message=trim($message);
            if(validate($message))
            {
                $client = new Google_Client();
                $client->setAuthConfig('client_secret.json');
                $client->setAccessType("offline");        // offline access
                $client->setIncludeGrantedScopes(true);   // incremental auth
                $client->setDeveloperKey("AIzaSyDHDuBK9PYzXHk_0EMeZy4FdgZd32_Rq1U");
                $client->authenticate($app['session']->get("code"));
                $client->addScope("https://www.googleapis.com/auth/gmail.send");
                $service = new Google_Service_Gmail($client);
                try {
                    $messageObj=new Google_Service_Gmail_Message($message);
                    $messageResponse = $service->users_messages->send($userEmail, $messageObj);
                    return "EMAIL_SENT";
                } catch (Exception $e) {
                    return "EMAIL_ERROR_".$e;
                }
            }
            else
            {
                return "INVALID_MESSAGE";
            }
        }
        else
        {
            return "INVALID_EMAIL_ID";
        }
    }
}
?>