<?php
/**
 *
 * @package       phpBB Extension - S3
 * @copyright (c) 2016 Austin Maddox
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace AustinMaddox\s3\event;

use Aws\Exception\MultipartUploadException;
use Aws\S3\S3Client;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    protected $bucket;
    protected $log;
    protected $region;
    protected $s3_client;

    /**
     * Constructor
     *
     * @param \phpbb\config\config     $config   Config object
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\user              $user     User object
     *
     * @return \AustinMaddox\s3\event\listener
     * @access public
     */
    public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user)
    {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;

        // TODO: Remove logging after dev/debug.
        $this->log = new Logger('phpbb');
        $this->log->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::DEBUG));

        $this->region = 'us-west-2';
        $this->bucket = 'warriormachines-phpbb-files';

        // Instantiate an AWS S3 client.
        $this->s3_client = new S3Client([
            'credentials' => [
                'key'    => $this->config['s3_aws_access_key_id'],
                'secret' => $this->config['s3_aws_secret_access_key'],
            ],
            'debug'       => false,
            'region'      => $this->region,
            'version'     => 'latest',
        ]);
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup'                => 'user_setup',
            'core.acp_board_config_edit_add' => 'acp_board_config_edit_add',
            'core.modify_uploaded_file'      => 'modify_uploaded_file',
            'core.validate_config_variable'  => 'validate_config_variable',
        ];
    }

    public function user_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'AustinMaddox/s3',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /**
     * Add config vars to ACP Board Settings
     *
     * @param object $event The event object
     *
     * @return null
     * @access public
     */
    public function acp_board_config_edit_add($event)
    {
        // Load language file.
        $this->user->add_lang_ext('AustinMaddox/s3', 's3_acp');

        // Add a config to the settings mode, after board_timezone.
        if ($event['mode'] == 'settings' && isset($event['display_vars']['vars']['board_timezone'])) {
            // Store display_vars event in a local variable
            $display_vars = $event['display_vars'];

            // Define the new config vars.
            $s3_config_vars = [
                's3_aws_access_key_id' => [
                    'lang'     => 'ACP_S3_AWS_ACCESS_KEY_ID',
                    'validate' => 's3_aws_access_key_id',
                    'type'     => 'text:40:20',
                    'explain'  => true,
                ],
            ];

            // Add the new config vars after board_timezone in the display_vars config array.
            $insert_after = ['after' => 'board_timezone'];
            $display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $s3_config_vars, $insert_after);

            // Update the display_vars event with the new array
            $event['display_vars'] = $display_vars;
        }
    }


    /**
     * Validate the AWS Access Key Id
     *
     * @param object $event The event object
     *
     * @return null
     * @access public
     */
    public function validate_config_variable($event)
    {
        $input = $event['cfg_array']['s3_aws_access_key_id'];

        // Check if the validate test is for s3.
        if (($event['config_definition']['validate'] == 's3_aws_access_key_id') && ($input !== '')) {
            // Store the error and input event data.
            $error = $event['error'];

            // Add error message if the input is not a valid AWS Access Key Id.
//            if (!preg_match('/^UA-\d{4,9}-\d{1,4}$/', $input)) {
//                $error[] = $this->user->lang('ACP_S3_AWS_ACCESS_KEY_ID', $input);
//            }

            // Update error event data.
            $event['error'] = $error;
        }
    }

    public function modify_uploaded_file($event)
    {
        $this->log->debug('##############################################');
        $this->log->debug(__METHOD__);
        $this->log->debug('##############################################');

        $result = null;
        $key = time();
        $data = 'path/to/file.png';

        try {
            $result = $this->s3_client->upload($this->bucket, $key, $data, 'public-read');
        } catch (MultipartUploadException $e) {
            $this->log->debug('MultipartUpload Failed', [$e->getMessage()]);
        }

        $object_url = ($result['ObjectURL']) ? $result['ObjectURL'] : $result['Location'];
        $this->log->debug($object_url);
    }
}
