<?php

/**
 * SAML authentication plugin
 *
 * @author     Andreas Gohr <gohr@cosmocode.de>
 * @author     Sam Yelman <sam.yelman@temple.edu>
 */
class action_plugin_saml extends DokuWiki_Action_Plugin
{

    /** @inheritdoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_request');
        $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE', $this, 'handle_loginform');
    }

    /**
     * Send the Federation Metadata about this Service Provider
     * Otherwise, handle Logout for SAML plugin
     * 
     * @param Doku_Event $event
     * @param mixed $param
     */
    public function handle_request(Doku_Event $event, $param)
    {
        global $ID;
		global $auth;
        $act = act_clean($event->data);
		if($act == "logout" && $this->getConf('use_slo') && 
			(isset($_GET["SAMLResponse"]) || isset($_GET["SAMLRequest"]))) {
			$auth->logOff();
		}
        if ($act != 'saml') return;
        $event->preventDefault();
        $event->stopPropagation();

        /** @var helper_plugin_saml $hlp */
        $hlp = plugin_load('helper', 'saml');
        $saml = $hlp->getSamlLib();

 

        try {
            header('Content-Type: application/samlmetadata+xml');
            header('Content-Disposition: attachment; filename="saml-metadata.xml"');
            $xml = $saml->getSettings()->getSPMetadata();
            echo $xml;
            exit();
        } catch (Exception $e) {
            die(hsc($e->getMessage()));
        }
    }

    /**
     * Disable the login forma and instead use a link to trigger login
     *
     * @param Doku_Event $event
     * @param $param
     */
    public function handle_loginform(Doku_Event $event, $param)
    {
        global $ID;
        global $conf;
        if ($conf['authtype'] != 'saml') return;

        $event->data = new Doku_Form(array());
        $event->data->addElement('<a href="' . wl($ID, array('do' => 'login')) . '">Login here</a>');
    }
}
