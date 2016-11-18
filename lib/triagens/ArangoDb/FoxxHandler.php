<?php

/**
 * ArangoDB PHP client: foxx upload
 *
 * @package   triagens\ArangoDb
 * @author    Tom Regner <thomas.regner@fb-research.de>
 * @copyright Copyright 2016, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A class for uploading Foxx application zips to a database
 *
 * @package   triagens\ArangoDb
 * @since     3.1
 */
class FoxxHandler extends Handler
{
    /**
     * Upload and install a foxx app.
     *
     * @throws Exception
     *
     * @param string $localZip          - the path to the local foxx-app zip-archive to upload/install
     * @param string mountPoint         - the mountpoint for the app, must begin with a '/'
     * @param array $options            - for future usage
     * @return array - the server response
     * 
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function installFoxxZip($localZip, $mountPoint, $options = array())
    {
        if (!file_exists($localZip)) {
            throw new ClientException("Foxx-Zip {$localZip} does not exist (or file is unreadable).");
        }
        
        $post = file_get_contents($localZip);
        $response = $this->getConnection()->post(Urls::URL_UPLOAD, $post);

        if ($response->getHttpCode() < 400) {
            $response = $this->getConnection()->put(Urls::URL_FOXX_INSTALL, json_encode(array('appInfo' => $response->getJson()['filename'], 'mount' => $mountPoint)));
            if ($response->getHttpCode() < 400) {
                return $response->getJson();
            } else { 
                throw new ClientException('Foxx-Zip install failed');
            }
        } else { 
            throw new ClientException('Foxx-Zip upload failed');
        }
    }

    
}
