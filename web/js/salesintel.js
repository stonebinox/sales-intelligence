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
            var sorted=[];
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
                email.inbound_count=emailCount;
                email.outbound_count=0;
                var pos=null;
                for(var j=0;j<sorted.length;j++){
                    var sort=sorted[j];
                    if(sort.idemail_master==emailID){
                        pos=j;
                        break;
                    }
                }
                if(!validate(pos)){
                    sorted.push(email);
                }
            }
            for(var i=0;i<sent.length;i++){
                var email=sent[i];
                var emailCount=0;
                var emailID=email.idemail_master;
                var otherEmail=email.from_email;
                for(var j=0;j<sent.length;j++){
                    var temp=sent[j];
                    if(temp.idemail_master!=emailID){
                        var otherEmail2=temp.from_email;
                        if(otherEmail2==otherEmail){
                            emailCount+=1;
                        }
                    }
                }
                // email.outbound_count=emailCount;
                var pos=null;
                for(var j=0;j<sorted.length;j++){
                    var sort=sorted[j];
                    if(sort.from_email==otherEmail){
                        pos=j;
                        break;
                    }
                }
                if(validate(pos)){
                    var storedEmail=sorted[pos];
                    storedEmail.outbound_count=emailCount;
                    sorted[pos]=storedEmail;
                }
            }
            var text='<table class="table"><thead><tr><th width="20%">Email</th><th width="20%">Subject</th><th width="20%">Inbound</th><th width="20%">Outbound</th><th width="20%">Actions</th></tr></thead><tbody>';
            $scope.emailCount=sorted.length;
            console.log(sorted);
            for(var i=0;i<sorted.length;i++){
                var email=sorted[i];
                var other=email.from_email;
                var subject=email.email_subject;
                var inboundCount=email.inbound_count;
                var outboundCount=email.outbound_count;
                var emailID=email.idemail_master;
                text+='<tr><td width="20%">'+other+'</td><td width="20%">'+subject+'</td><td width="20%">'+inboundCount+'</td><td width="20%">'+outboundCount+'</td><td width="20%"><div class="btn-group"><button type="button" class="btn btn-primary btn-xs">Send email</button><button type="button" ng-click="showEmailContent('+emailID+')" class="btn btn-default btn-xs">Read latest email</button></div></td></tr>';
            }
            text+='</tbody></table>';
            $(".panel-body").html(text);
            $compile($(".panel-body"))($scope);
        }
    };
    $scope.showEmailContent=function(emailID){
        if(validate(emailID)){
            var emails=$scope.emails;
            var pos=null;
            for(var i=0;i<emails.length;i++){
                var email=emails[i];
                if(email.idemail_master==emailID){
                    pos=i;
                    break;
                }
            }
            if(validate(pos)){
                var email=emails[pos];
                var content=$.trim(nl2br(stripslashes(email.email_body)));
                if(validate(content)){
                    messageBox("Email Content",content);
                }
                else{
                    messageBox("Email Content","This email's content has not yet been synced.");    
                }
            }
            else{
                messageBox("Email Content","This email's content has not yet been synced.");
            }
        }
    };
});