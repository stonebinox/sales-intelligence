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
            $em="SELECT idemail_master FROM email_master WHERE stat='1' AND user_master_iduser_master='$userID' ORDER BY idemail_master DESC";
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
}
?>