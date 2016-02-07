<?php
/**
 *
 * @package       phpBB Extension - S3
 * @copyright (c) 2016 Austin Maddox
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace AustinMaddox\s3\event;

/**
 * @ignore
 */
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class listener implements EventSubscriberInterface
{
    protected $log;
    protected $s3_client;

    public function __construct()
    {
        // TODO: Remove this after dev/debug.
        $this->log = new Logger('phpbb');
        $this->log->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::DEBUG));

        // Instantiate an AWS S3 client.
        $this->s3_client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-2',
        ]);
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.modify_uploaded_file' => 'modify_uploaded_file_put_to_s3',
        ];
    }

    public function modify_uploaded_file_put_to_s3($event)
    {
        $this->log->debug('##############################################');
        $this->log->debug(__METHOD__);
        $this->log->debug('##############################################');
        try {
            $this->s3_client->putObject([
                'Bucket' => 'warriormachines-phpbb-files',
                'Key'    => 'my-object',
                'Body'   => fopen('/path/to/file', 'r'),
                'ACL'    => 'public-read',
            ]);
        } catch (S3Exception $e) {
            $this->log->error('There was an error uploading the file.');
        }
    }
}
