<?php
/**
 *
 * @package       phpBB Extension - S3
 * @copyright (c) 2016 Austin Maddox
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace AustinMaddox\s3\acp;

class main_module
{
    var $u_action;

    function main($id, $mode)
    {
        global $db, $user, $auth, $template, $cache, $request;
        global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

        $user->add_lang('acp/common');
        $this->tpl_name = 's3_body';
        $this->page_title = $user->lang('ACP_S3_TITLE');
        add_form_key('AustinMaddox/s3');

        if ($request->is_set_post('submit')) {
            if (!check_form_key('AustinMaddox/s3')) {
                trigger_error('FORM_INVALID');
            }

            $config->set('s3_aws_access_key_id', $request->variable('s3_aws_access_key_id', 0));

            trigger_error($user->lang('ACP_S3_SETTING_SAVED') . adm_back_link($this->u_action));
        }

        $template->assign_vars([
            'U_ACTION'             => $this->u_action,
            'S3_AWS_ACCESS_KEY_ID' => $config['s3_aws_access_key_id'],
        ]);
    }
}