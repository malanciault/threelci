<?php

namespace Malanciault\Threelci\Libraries;

class Authentication
{
    private $ci = false;

    function __construct()
    {
        $this->ci =& get_instance();
    }

    public function login_user($data, $remember = false)
    {
        $update = array(
            'last_login' => now_str(),
        );

        $this->ci->user_model->edit_user($update, $data['user_id']);

        $user = $this->ci->user_model->get_user_by_id($data['user_id']);
        $data['org_id'] = $user['org_id'];
        /* todo: bring all the data info in this method instead of in controller/auth/login, choose_password, reset_password, etc.... */

        $this->ci->session->set_userdata($data);


        if ($remember && isset($this->ci->auth_token_model)) {
            $auth_token_data['auth_token_selector'] = $data['user_id'] . time();
            $auth_token_data['auth_token_hashed_validator'] = hash('sha256', $data['user_id'] . time());
            $auth_token_data['auth_token_user_id'] = $data['user_id'];
            $auth_token_data['auth_token_expires '] = date('Y-m-d', strtotime("+60 days"));
            $this->ci->auth_token_model->insert($auth_token_data);

            $cookie = array(
                'name' => 'illuxi-auth-token',
                'value' => $auth_token_data['auth_token_selector'],
                'expire' => '86400',
            );
            $this->ci->input->set_cookie($cookie);
        }
    }

    public function remembered()
    {
        $ret = false;
        if (!$this->ci->session->has_userdata('is_user_login') && $token = get_cookie('illuxi-auth-token')) {
            $ret = $this->ci->auth_token_model->remembered($token);
        }
        return $ret;
    }
}