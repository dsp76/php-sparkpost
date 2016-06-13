<?php

namespace SparkPost;

class Transmission extends Resource
{
    protected $customHeaders = array();

    public function __construct(SparkPost $sparkpost)
    {
        parent::__construct($sparkpost, 'transmissions');
    }

    public function fixBlindCarbonCopy($payload)
    {
        //TODO: Manage recipients. "Vincent Song <vincentsong@sparkpost.com>"
        
        $modifiedPayload = $payload;
        $bccList = &$modifiedPayload['bcc'];
        $recipientsList = &$modifiedPayload['recipients'];
        
        //Format: Original Recipient" <original.recipient@example.com>
        //if a name exists, then do "name" <email>. Otherwise, just do <email>
        if(isset($modifiedPayload['recipients'][0]['name']))
        {
            $originalRecipient = '"' . $modifiedPayload['recipients'][0]['name'] 
                . '" <' . $modifiedPayload['recipients'][0]['address'] . '>';
        } else {
            $originalRecipient = '<' . $modifiedPayload['recipients'][0]['address'] 
                . '>';
        }

        //loop through all BCC recipients
        if(isset($bccList)){
            foreach ($bccList as $bccRecipient) { 
                $newRecipient = [
                        'address' => $bccRecipient['address'],
                        'header_to' => $originalRecipient,
                ];
                array_push($recipientsList, $newRecipient);
            }
        }
        
        //Delete the BCC object/array
        unset($modifiedPayload['bcc']); 

        return $modifiedPayload;
    }

    public function fixCarbonCopy($payload)
    {
        $ccCustomHeadersList = "";
        $modifiedPayload = $payload;
        $ccList = &$modifiedPayload['cc'];
        $recipientsList = &$modifiedPayload['recipients'];
        
        //var_dump($ccList);
        
        //if a name exists, then do "name" <email>. Otherwise, just do <email>
        if(isset($modifiedPayload['recipients'][0]['name'])) {
            $originalRecipient = '"' . $modifiedPayload['recipients'][0]['name'] 
                . '" <' . $modifiedPayload['recipients'][0]['address'] . '>';
        } else {
            $originalRecipient = '<' . $modifiedPayload['recipients'][0]['address'] 
                . '<';
        }
        
        if(isset($ccList)){
             foreach ($ccList as $ccRecipient) {
                $newRecipient = [
                        'address' => $ccRecipient['address'],
                        'header_to' => $originalRecipient,
                ];

                //if name exists, then use "Name" <Email> format. Otherwise, just email will suffice. 
                if(isset($ccRecipient['name'])) {
                    $ccCustomHeadersList = $ccCustomHeadersList . ' "' . $ccRecipient['name'] 
                        . '" <' . $ccRecipient['address'] . '>,';
                } else {
                    $ccCustomHeadersList = $ccCustomHeadersList . ' ' . $ccRecipient['address'];
                }
                array_push($recipientsList, $newRecipient);
            }   

            if(!empty($ccCustomHeadersList)){ //If there are CC'd people
                $this->customHeaders = array("CC" => $ccCustomHeadersList);
            }
            
            //Edits customHeaders and adds array of CSV list of CC emails
        }
        
        //delete CC
        unset($modifiedPayload['cc']);
        
        return $modifiedPayload;
    }

    public function post($payload)
    {
        $modifiedPayload = $this->fixBlindCarbonCopy($payload); //Accounts for any BCCs
        $modifiedPayload = $this->fixCarbonCopy($modifiedPayload); //Accounts for any CCs
        var_dump($this->customHeaders);
        return parent::post($modifiedPayload, $this->customHeader);
    }
}

?>