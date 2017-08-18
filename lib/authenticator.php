<?php

class Authenticator
{
    protected $inputLabel = 'Response';
    public $scripts = array();
    
    static public function init()
    {
        TwoFactorAuthPlugin::registerModule(
            'xauth',
            static::$name,
            get_called_class()
        );
        return true;
    }



    public function prepare($user, $args)
    {
        $this->user = $user;
    }
    
    public function showForm($out)
    {
       $out->elementStart('div');
       if ($this->inputLabel) {
           $out->element('label', null, $this->inputLabel . ": ");
           $out->input('response-input', null);
           $out->submit('response-submit', "Submit");
       } else {
           $out->hidden('response-input', null);
           $out->submit("response-submit", "", "hidden");
       }
       $out->elementEnd('div');    
    }
     
    public function validate($response)
    {
        throw new Exception("authentication response invalid");
    }
}