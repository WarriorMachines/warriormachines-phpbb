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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    protected $bucket;
    protected $region;
    protected $s3_client;

    /**
     * Constructor
     *
     * @param \phpbb\config\config     $config   Config object
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\user              $user     User object
     *
     * @return \AustinMaddox\s3\event\main_listener
     * @access public
     */
    public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user)
    {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;

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
            'core.user_setup'               => 'user_setup',
            'core.modify_uploaded_file'     => 'modify_uploaded_file',
            'core.validate_config_variable' => 'validate_config_variable',
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
        global $phpbb_root_path, $config;

        $filedata = $event['filedata'];

        $result = null;
        $path_parts = pathinfo($filedata['real_filename']);
        $key = uniqid(md5($path_parts['filename'])) . '.' . $filedata['extension'];
        $body = file_get_contents($phpbb_root_path . $config['upload_path'] . '/' . $filedata['physical_filename']);

        try {
            $result = $this->s3_client->upload($this->bucket, $key, $body, 'public-read');
        } catch (MultipartUploadException $e) {
            error_log('MultipartUpload Failed', [$e->getMessage()]);
        }

        $object_url = ($result['ObjectURL']) ? $result['ObjectURL'] : $result['Location'];

        error_log($object_url);
    }
}
