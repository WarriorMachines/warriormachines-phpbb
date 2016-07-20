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
            'core.user_setup'                               => 'user_setup',
            'core.validate_config_variable'                 => 'validate_config_variable',
            'core.modify_uploaded_file'                     => 'modify_uploaded_file',
            'core.delete_attachments_from_filesystem_after' => 'delete_attachments_from_filesystem_after',
            'core.posting_modify_message_text'              => 'posting_modify_message_text',
            'core.parse_attachments_modify_template_data'   => 'parse_attachments_modify_template_data',
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
        $key = $filedata['physical_filename'];
        $file_path = $phpbb_root_path . $config['upload_path'] . '/' . $filedata['physical_filename'];
        $body = file_get_contents($file_path);

        $result = null;
        try {
            $result = $this->s3_client->upload($this->bucket, $key, $body, 'public-read', ['params' => ['ContentType' => $filedata['mimetype']]]);
        } catch (MultipartUploadException $e) {
            error_log('MultipartUpload Failed', [$e->getMessage()]);
        }

        $object_url = ($result['ObjectURL']) ? $result['ObjectURL'] : $result['Location'];

        error_log($object_url);
    }

    public function delete_attachments_from_filesystem_after($event)
    {
        foreach ($event['physical'] as $physical_file) {
            $result = $this->s3_client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $physical_file['filename'],
            ]);
            error_log(print_r($result, true));
        }
    }

    /**
     * Failed attempt at catching deletes of `is_orphan` attachments.
     * https://www.phpbb.com/community/viewtopic.php?f=461&t=2380221
     *
     * @param $event
     */
    public function posting_modify_message_text($event)
    {
        error_log(__METHOD__);
        error_log('##############################################');

        error_log(print_r($event['post_data'], true));
    }

    public function parse_attachments_modify_template_data($event)
    {
//        error_log(print_r($event['attachment'], true));
//        error_log(print_r($event['block_array'], true));

        $block_array = $event['block_array'];
        $block_array['THUMB_IMAGE'] = 'http://' . $this->bucket . '.s3.amazonaws.com/' . $event['attachment']['physical_filename'];
        $event['block_array'] = $block_array;
    }
}
