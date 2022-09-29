<?php
namespace ArangoDBClient;

/**
 * ArangoDB PHP client: Represent parts of a Multipart request
 *
 * @package   ArangoDBClient
 * @author    Tom Regner <tom.regner@fb-research.de>
 * @copyright Copyright 2022, ArangoDB GmbH, Cologne, Germany
 */
class Multipart extends OptionHelper
{
    /**
     * Multipart prefix
     */
    const MULTIPART_PREFIX = '--';

    /**
     * Multipart suffix
     */
    const MULTIPART_SUFFIX = self::MULTIPART_PREFIX;

    /**
     * Multipart form-data mime type
     */
    const MULTIPART_FORMDATA = 'form-data';

    /**
     * Content-Type attribute filename
     */
    const MULTIPART_FILENAME = 'filename';

    /**
     * Content-Transfer-Encoding attribute filename
     */
    const MULTIPART_TRANSFER_ENCODING = 'transfer-encoding';

    /**
     * Content-Type attribute name
     */
    const MULTIPART_NAME = 'name';

    /**
     * Content-Type
     */
    const MULTIPART_MIMETYPE = 'mime-type';

    /**
     * Identifies the parts content
     */
    const MULTIPART_VALUE = 'value';

    /**
     * Transfer encoding binary
     */
    const MULTIPART_ENCODING_BINARY = 'binary';

    /**
     * Transfer encoding base64
     */
    const MULTIPART_ENCODING_BASE64 = 'base64';

    /**
     * Transfer encoding quoted-printable
     */
    const MULTIPART_ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    /**
     * Unneeded for now.
     * @return void
     * @throws ClientException
     */
    final protected function validate() : void
    {
        if ($this->values[self::MULTIPART_MIMETYPE] && empty($this->values[self::MULTIPART_FILENAME])) {
            throw new ClientException("Mimetype must only be set for file-type fields; '". self::MULTIPART_FILENAME . "' missing.");
        }
        if ($this->values[self::MULTIPART_TRANSFER_ENCODING] && empty($this->values[self::MULTIPART_FILENAME])) {
            throw new ClientException("Transfer encoding must only be set for file-type fields; '". self::MULTIPART_FILENAME . "' missing.");
        }
    }
}
