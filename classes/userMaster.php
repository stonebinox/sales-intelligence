<?php
/*-----------------------------
Author: Anoop Santhanam
Date Created: 24/12/17 11:27
Last modified: 24/12/17 11:27
Comments: Main class file for
user_master table.
-----------------------------*/
class userMaster
{
    public $app=NULL;
    public $userValid=false;
    private $user_id=NULL;
    function __construct($userID=NULL)
    {
        $this->app=$GLOBALS['app'];
        if(validate($userID))
        {
            $this->user_id=secure($userID);
            $this->userValid=$this->verifyUser();
        }
    }
    function verifyUser()
    {
        if(validate($this->user_id))
        {
            $app=$this->app;
            $userID=$this->user_id;
            $um="SELECT iduser_master FROM user_master WHERE stat='1' AND iduser_master='$userID'";
            $um=$app['db']->fetchAssoc($um);
            if(validate($um))
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
    function getUser()
    {
        if($this->userValid)
        {
            $app=$this->app;
            $userID=$this->user_id;
            $um="SELECT * FROM user_master WHERE iduser_master='$userID'";
            $um=$app['db']->fetchAssoc($um);
            if(validate($um))
            {
                return $um;
            }
            else
            {
                return "INVALID_USER_ID";
            }
        }
        else
        {
            return "INVALID_USER_ID";
        }
    }
    function addUser($emailID)
    {
        $emailID=secure($emailID);
        if((validate($emailID))&&(filter_var($emailID, FILTER_VALIDATE_EMAIL)))
        {
            $app=$this->app;
            $um="SELECT iduser_master FROM user_master WHERE stat='1' AND user_email='$emailID'";
            echo $um;
            $um=$app['db']->fetchAssoc($um);
            if(!validate($um))
            {
                $in="INSERT INTO user_master (timestamp,user_email) VALUES (NOW(),'$emailID')";
                $in=$app['db']->executeQuery($in);
                $um="SELECT iduser_master FROM user_master WHERE stat='1' AND user_email='$emailID'";
                $um=$app['db']->fetchAssoc($um);
            }
            $userID=$um['iduser_master'];
            $app['session']->set("uid",$userID);
            return "USER_AUTHENTICATED";
        }
        else
        {
            return "INVALID_EMAIL_ID";
        }
    }
}
?>