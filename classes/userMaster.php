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
}
?>