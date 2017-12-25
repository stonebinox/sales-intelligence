var app=angular.module("si",[]);
app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
});
app.controller("mails",function($scope,$compile,$http){
    $scope.emails=[];
    $scope.emailCount=0;
    $scope.user_id=null;
    $scope.getEmails=function(){
        $(".panel-body").html('<p class="text-center"><img src="images/ripple.gif" border=0 alt="Loading" width=30 height=30></p>');
        $http.get("emails")
        .then(function success(response){
            response=response.data;
            console.log(response);
            $(".panel-body").html('');
            if(typeof response=="object"){
                $scope.emails=response;
                $scope.emailCount=$scope.emails.length;
                $scope.matchEmails();
            }
            else{
                response=$.trim(response);
                switch(response){
                    case "INVALID_PARAMETERS":
                    default:
                    messageBox("Problem","Something went wrong while fetching your data. Please try again later. This is the error we see: "+response);
                    break;
                    case "INVALID_USER_ID":
                    window.location="https://dusthq-sales-intelligence.herokuapp.com/";
                    break;
                }
            }
        },
        function error(response){
            console.log(response);
            messageBox("Problem","Something went wrong while fetching your data. Please try again later.");
        });
    };
    $scope.matchEmails=function(){
        if(validate($scope.emails)){
            var emails=$scope.emails;
            var inbox=[];
            var sent=[];
            for(var i=0;i<emails.length;i++){
                var email=emails[i];
                var emailMailbox=email.email_mailbox;
                if(emailMailbox=="Inbox"){
                    inbox.push(email);
                }
                else if(emailMailbox=="Sent"){
                    sent.push(email);
                }
            }
            for(var i=0;i<inbox.length;i++){
                var email=inbox[i];
                var emailCount=0;
                var emailID=email.idemail_master;
                var otherEmail=email.from_email;
                for(var j=0;j<inbox.length;j++){
                    var temp=inbox[j];
                    if(temp.idemail_master!=emailID){
                        var otherEmail2=temp.from_email;
                        if(otherEmail2==otherEmail){
                            emailCount+=1;
                        }
                    }
                }
                email.email_count=emailCount;
                inbox[i]=email;
            }
            console.log(inbox);
        }
    };
});