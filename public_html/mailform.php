<?php
/**
Copyright (c) 2009, Simeon Franklin

All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided
that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions
      and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of
      conditions and the following disclaimer in the documentation and/or other materials provided
      with the distribution.
    * Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to
      endorse or promote products derived from this software without specific prior written permission.

nTHIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


/***********************************
 *
 *Instructions for configuring mailform.php:
 *
 *mailform.php is a secure (can't be redirected) mail form script. It does not create the
 *html for your forms but will accept a POST from a form and email the form recipient. It
 *can also be configured to help validate your form (both after submittal and with
 *javascript before the form is submitted).
 *
 *To specify the email address(es) the message should be mailed to fill out the line that
 *looks like:
 *
 *$email = "email1@gmail.com";
 *
 *You can use multiple email addresses separated by commas if you want multiple email addresses
 *to receive notifications... You can also edit the following line to edit the $subject of email
 *notification.
 *
 *To specify the required field(s) edit the line that looks like:
 *
 * $required = "name, email";
 *
 *to add any additional required fields. The fields should be a comma separated list
 *of the fieldnames as defined in the name attribute of the form input control in your
 *html such as:
 *
 *<input type='text' name='email' value='you@gmail.com'>
 *
 *Just be sure that you've placed the script somewhere in your html folder, test that it runs
 *by going to it in your browser and adding the test paramer (eg: open
 *http://www.yourdomain.com/mailform.php?test=1) and verify
 *that you see a sucess message... Edit your <form> tag on your html page and make sure that
 *method="POST" and action="/mailform.php". Test submit your form from your browser and check
 *the email address you specified...
 *
 *ADVANCED CONFIGURATION:
 *
 *To be sure that required fields are checked on the form by
 *javascript add a script tag in the head of your html document like
 *
 *<script type="text/javascript" src="mailform.php?js=1"></script>
 *
 *If you'd like to have a graceful sucess page just edit the line that looks like
 *
 *$sucess_redirect = '';
 *
 *to contain the absolute path your sucess page. To redirect to a page called thankyou.html, for
 *example, just edit the line to say:
 *
 *$sucess_redirect = '/thankyou.html';
 *
 *To specify the reply email address (email sent via PHP is often sent from a "nobody" or "server"
 * account) fill out the $from variable. Values can take the form of "name <email@domain.com" like:
 *
 *$from = "Admin <noreply@foo.com>";
 *
 ********************************************************************************/
###########################################
#CONFIGURATION SECTION - feel free to edit
$email = 'debra@volkmanseed.com';
$subject = 'mailform.php message from volkmanseed.com';
$required = 'name,email';
$sucess_message = 'Thank you for contacting us!';
$form_id = "mailform";
$sucess_redirect = '/index.html';
$template = '';
$from = "Volkman Seed Company <debra@volkmanseed.com>";
$from_field = 'email';


###########################################
#CODE FOLLOWS - do not change

assert_options(ASSERT_BAIL);
assert('$email');
$template_str =<<<HTML
<html>
  <head>
    <title>Form Submission Received</title>
  </head>
  <body>
    {content}
  </body>
</html>
HTML;


if($_GET['js'] and $required)
{
    $required = explode(",", $required);
    $required = 'req = [\'' . implode("','", $required) . '\'];';
    header("Content-Type: text/javascript");
    $js =<<<JS
      if(!jQuery){
         document.write("<script src='http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js' type='text/javascript'></script>");
      }
     JS = "<script type='text/javascript'>\
     $(document).ready(function(){\
           $('form#$form_id').submit(function(){\
                    $('.error').hide();\
                    $required\
                    frm = true;\
                    for(var i in req){\
                        var val = $('[name=\"' + req[i] + '\"]').val();\
                        if(!val){\
                            frm = false;\
                            $('[name=\"' + req[i] + '\"]').after('<span class=\"error\">Required!</span>');\
                        }\
                    }\
                    return(frm);\
                 });\
    });\
    </script>";
    document.write(JS);
JS;
    print($js);
}
elseif(count($_POST))
{
    $msg_text = "";
    $output = "";
    $sucess = true;
    if($template)
        $template_str = file_get_contents($template);

    $required ? $required = explode(",", $required) : $required = array();
    $req_fields = array();
    foreach($required as $f)
        $req_fields[trim($f)] = true;
    foreach($_POST as $k=>$v)
    {
        $msg_text .= ucspace($k) . ": " . $v . "\n";
        if($v)
            $req_fields[$k] = false;
    }
    $req_fields = array_filter($req_fields);
    if(count($req_fields)>0)
    {
        $sucess = false;
        foreach($req_fields as $k=>$v)
        {
            $output .= '"' . ucspace($k) . '" is a required field!<br>';
        }
    }
    if(!$sucess)
    {
        $output .= 'Click the "back" button in your browser to correct the errors.';
        $output = str_replace("{content}",$output, $template_str);
        print($output);
        exit();
    }
    else
    {
        if(!$email)
            die('$email is not set!');
        $headers = array();
        if($from_field)
            $headers[] = "From: " . $_POST[$from_field]; 
        elseif($from)
            $headers[] = "From: $from";
	
        $headers = implode("\r\n",$headers);        

        if(count($_FILES) > 0)
        {
            $email_message = '';
            $file = reset($_FILES);
            $fh = fopen($file['tmp_name'],'rb');
            $data = fread($fh, filesize($file['tmp_name']));
            fclose($fh);

            $semi_rand = md5(time());
            $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

            $headers .= "\nMIME-Version: 1.0\n" .
            "Content-Type: multipart/mixed;\n" .
            " boundary=\"{$mime_boundary}\"";

            $email_message = "This is a multi-part message in MIME format.\n\n" .
            "--{$mime_boundary}\n" .
            "Content-Type:text; charset=\"iso-8859-1\"\n" .
            "Content-Transfer-Encoding: 7bit\n\n" .
            $msg_text . "\n\n";

            $data = chunk_split(base64_encode($data));

            $email_message .= "--{$mime_boundary}\n" .
            "Content-Type: application/octet-stream;\n" .
            " name=\"{$file['name']}\"\n" .
            "Content-Transfer-Encoding: base64\n\n" .
            $data . "\n\n" .
            "--{$mime_boundary}--\n";
            $msg_text = $email_message;
        }
        mail($email, $subject, $msg_text, $headers);
        if($sucess_redirect)
            header("Location: $sucess_redirect");
        else
        {
            print(str_replace("{content}",$sucess_message, $template_str));
            exit();
        }
    }
}
elseif($_GET['test'])
{
    if($template)
        $template_str = file_get_contents($template);
    $output = str_replace("{content}","<h1>mailform.php is sucessfully installed!</h1>", $template_str);
    print($output);
    exit();
}

function ucspace($k)
{
  return(ucwords(str_replace('_',' ', $k)));
}
