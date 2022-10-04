<?php

/**
 * ArangoDB PHP client: foxx upload
 *
 * @package   ArangoDBClient
 * @author    Tom Regner <thomas.regner@fb-research.de>
 * @copyright Copyright 2016, triagens GmbH, Cologne, Germany
 * @copyright Copyright 2022, ArangoDB GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

use ArangoDBClient\HttpHelper;
use ArangoDBClient\Urls;
use ArangoDBClient\UrlHelper;
use ArangoDBClient\ServerException;
use ArangoDBClient\ClientException;
use ArangoDBClient\Multipart;

/**
 * A class for uploading Foxx application zips to a database
 *
 * @package   ArangoDBClient
 * @since     3.1
 */
class FoxxHandler extends Handler
{
    /* Field names */

    /**
     * Name of the dependencie field
     */
    const FOXX_APP_DEPENDENCIES = 'dependencies';
    /**
     * Name of the configuration field
     */
    const FOXX_APP_CONFIGURATION = 'configuration';

    /* Url parameter names */

    /**
     * Name of the setup parameter
     */
    const FOXX_APP_SETUP = 'setup';
    /**
     * Name of the teardown parameter
     */
    const FOXX_APP_TEARDOWN = 'teardown';
    /**
     * Name of the legacy parameter
     */
    const FOXX_APP_LEGACY = 'legacy';
    /**
     * Name of development parameter
     */
    const FOXX_APP_DEVELOPMENT = 'development';

    /**
     * Name of the force parameter
     */
    const FOXX_APP_FORCE = 'force';

    /**
     * Name of the exclude system parameter
     */
    const FOXX_APP_EXCLUDE_SYSTEM = 'excludeSystem';

    /* Default values and custom consts */

    /**
     * Default values for the options above
     */
    const FOXX_APP_DEFAULT_PARAMS = [
        self::FOXX_APP_DEVELOPMENT => false,
        self::FOXX_APP_LEGACY => false,
        self::FOXX_APP_SETUP => true,
        self::FOXX_APP_TEARDOWN => true,
        self::FOXX_APP_FORCE => false,
        self::FOXX_APP_EXCLUDE_SYSTEM => true,
    ];

    /**
     * Multipart boundary for foxx app api calls
     */
    const FOXX_APP_MIME_BOUNDARY = 'XXXfoxxhandlerXXX';

    /**
     * Upload and install a foxx app.
     *
     * @throws ClientException
     *
     * @param string $localZip   - the path to the local foxx-app zip-archive to upload/install
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - You can pass configuration (array), dependencies (array) and control options
     *                             (bool) legacy, development, setup, teardown
     *                             Defaults are
     *                             - configuration empty
     *                             - dependencies empty
     *                             - control options: see FoxxHandler::FOXX_APP_DEFAULT_PARAMS
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated Use installService()
     */
    public function installFoxxZip($localZip, $mountPoint, array $options = [])
    {
        $this->installService($localZip, $mountPoint, $options);
    }

    /**
     * Upload and install a foxx app.
     *
     * @throws ClientException
     *
     * @param string $localZip   - the path to the local foxx-app zip-archive to upload/install
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - You can pass configuration (array), dependencies (array) and control options
     *                             (bool) legacy, development, setup, teardown
     *                             Defaults are
     *                             - configuration empty
     *                             - dependencies empty
     *                             - control options: see FoxxHandler::FOXX_APP_DEFAULT_PARAMS
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function installService($localZip, $mountPoint, array $options = [])
    {
        return $this->zipAction($localZip, $mountPoint, $options, false, false);
    }

    /**
     * Upload a zip amd upgrade an existing service.
     *
     * @throws ClientException
     *
     * @param string $localZip   - the path to the local foxx-app zip-archive to upload/install
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - You can pass configuration (array), dependencies (array) and control options
     *                             (bool) legacy, development, setup, teardown, force
     *                             Defaults are
     *                             - configuration empty
     *                             - dependencies empty
     *                             - control options: see FoxxHandler::FOXX_APP_DEFAULT_PARAMS
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function upgradeService($localZip, $mountPoint, array $options = [])
    {
        return $this->zipAction($localZip, $mountPoint, $options, true, false);
    }

    /**
     * Upload a zip amd replace an existing service.
     *
     * @throws ClientException
     *
     * @param string $localZip   - the path to the local foxx-app zip-archive to upload/install
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - You can pass configuration (array), dependencies (array) and control options
     *                             (bool) legacy, development, setup, teardown, force
     *                             Defaults are
     *                             - configuration empty
     *                             - dependencies empty
     *                             - control options: see FoxxHandler::FOXX_APP_DEFAULT_PARAMS
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function replaceService($localZip, $mountPoint, array $options = [])
    {
        return $this->zipAction($localZip, $mountPoint, $options, false, true);
    }

    /**
     * Install/Upgrade/Replace a foxx service via zip-bundle
     *
     * @throws ClientException
     *
     * @param string $localZip   - the path to the local foxx-app zip-archive to upload/install
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - You can pass configuration (array), dependencies (array) and control options
     * @param bool $upgrade      - If true, try to upgrade an existing service, if this is true, $replace will be ignored
     * @param bool $replace      - if true replace an existing service; if $upgrade is true, this will be ignored
     */
    protected function zipAction($localZip, $mountPoint, array $options, bool $upgrade = false, bool $replace = false)
    {
        if (!file_exists($localZip)) {
            throw new ClientException("Foxx-Zip {$localZip} does not exist (or file is unreadable).");
        }

        $dependencies = [];
        if (!empty($options[self::FOXX_APP_DEPENDENCIES])) {
            $dependencies = $options[self::FOXX_APP_DEPENDENCIES];
            unset($options[self::FOXX_APP_DEPENDENCIES]);
        }
        $configuration = [];
        if (!empty($options[self::FOXX_APP_CONFIGURATION])) {
            $configuration = $options[self::FOXX_APP_CONFIGURATION];
            unset($options[self::FOXX_APP_CONFIGURATION]);
        }

        try {
            $post = file_get_contents($localZip);
            $bodyParts = [
                new Multipart([
                    Multipart::MULTIPART_NAME => 'configuration',
                    Multipart::MULTIPART_VALUE => json_encode($configuration, JSON_FORCE_OBJECT),
                ]),
                new Multipart([
                    Multipart::MULTIPART_NAME => 'dependencies',
                    Multipart::MULTIPART_VALUE => json_encode($dependencies, JSON_FORCE_OBJECT),
                ]),
                new Multipart([
                    Multipart::MULTIPART_NAME => 'source',
                    Multipart::MULTIPART_VALUE => $post,
                    Multipart::MULTIPART_TRANSFER_ENCODING => Multipart::MULTIPART_ENCODING_BINARY,
                    Multipart::MULTIPART_FILENAME => basename($localZip),

                ]),
            ];
            $bodyStr = HttpHelper::buildMultiPartFormDataBody(self::FOXX_APP_MIME_BOUNDARY, ...$bodyParts);
            $optionNames = [self::FOXX_APP_LEGACY, self::FOXX_APP_DEVELOPMENT, self::FOXX_APP_SETUP];
            if (true === $upgrade|| true === $replace) {
                $optionNames[] = self::FOXX_APP_FORCE;
            }
            $params = static::buildParameterArray($optionNames, $mountPoint, $options);
            if (true === $upgrade) {
                $response = $this->getConnection()->patch(
                   UrlHelper::appendParamsUrl(Urls::URL_FOXX_UPGRADE, $params),
                    $bodyStr,
                    ["Content-Type" => "multipart/form-data; boundary=" . self::FOXX_APP_MIME_BOUNDARY]
                );
            } elseif (true === $replace) {
                $response = $this->getConnection()->put(
                    UrlHelper::appendParamsUrl(Urls::URL_FOXX_REPLACE, $params),
                    $bodyStr,
                    ["Content-Type" => "multipart/form-data; boundary=" . self::FOXX_APP_MIME_BOUNDARY]
                );
            } else {
                $response = $this->getConnection()->post(
                    UrlHelper::appendParamsUrl(Urls::URL_FOXX_INSTALL, $params),
                    $bodyStr,
                    ["Content-Type" => "multipart/form-data; boundary=" . self::FOXX_APP_MIME_BOUNDARY]
                );
            }
            $code = $response->getHttpCode();
            $response = $response->getJson();
            if (true === $upgrade || true === $replace) {
                if (200 !== $code) {
                    throw new ClientException("Foxx-Zip replace/upgrade failed: {$response['errorMessage']} (errno {$response['errorNum']})");
                }
            } else {
                if (201 !== $code) {
                    throw new ClientException("Foxx-Zip install failed: {$response['errorMessage']} (errno {$response['errorNum']})");
                }
            }

            return $response;
        } catch (ServerException $e) {
            throw new ClientException($e);
        }
    }

    /**
     * Remove a foxx-app.
     *
     * @throws ClientException
     *
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - you can pass the control option teardown (bool)
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated use uninstallService()
     */
    public function removeFoxxApp($mountPoint, array $options = [])
    {
        return $this->uninstallService($mountPoint, $options);
    }

    /**
     * Remove a foxx-app.
     *
     * @throws ClientException
     *
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - you can pass the control option teardown (bool)
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstallService($mountPoint, array $options = [])
    {

        try {
            $params = static::buildParameterArray([self::FOXX_APP_TEARDOWN], $mountPoint, $options);
            $url = UrlHelper::appendParamsUrl(Urls::URL_FOXX_UNINSTALL, $params);
            $response = $this->getConnection()->delete($url);
            if ($response->getHttpCode() === 204) {
                return $response->getJson();
            }

            throw new ClientException(sprintf('Foxx uninstall failed (Code: %d)', $response->getHttpCode()));
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Service not found') {
                throw new ClientException(sprintf('Mount point %s not present.', $mountPoint));
            }
            throw new ClientException($e->getMessage());
        }
    }

    /**
     * Retrieve a list of meta data for all installed services
     *
     * @throws ClientException
     *
     * @param array  $options    - you cann pass the option excludeSystem (bool)
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function services(array $options = [])
    {

        try {
            $params = static::buildParameterArray([self::FOXX_APP_EXCLUDE_SYSTEM], '', $options);
            $url = UrlHelper::appendParamsUrl(Urls::URL_FOXX, $params);
            $response = $this->getConnection()->get($url);
            $code = $response->getHttpCode();
            if (200 === $code) {
                return $response->getJson();
            }

            throw new ClientException(sprintf('Error when fetching services meta data (Code: %d)', $response->getHttpCode()), $response->getJson());
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Service not found') {
                throw new ClientException(sprintf('Mount point %s not present.', $mountPoint));
            }
            throw new ClientException($e->getMessage());
        }
    }

    /**
     * Retrieve service meta data
     *
     * @throws ClientException
     *
     * @param string $mountPoint - the mount-point for the app, must begin with a '/'
     * @param array  $options    - for future use
     *
     * @return array - the server response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function serviceInfo($mountPoint, array $options = [])
    {

        try {
            $params = static::buildParameterArray([], $mountPoint, $options);
            $url = UrlHelper::appendParamsUrl(Urls::URL_FOXX_SERVICE, $params);
            $response = $this->getConnection()->get($url);
            $code = $response->getHttpCode();
            if (200 === $code) {
                return $response->getJson();
            } elseif (400 === $code) {
                throw new ClientException("Service unknown: {$mountPoint}");
            }

            throw new ClientException(sprintf('Error when fetching service meta data (Code: %d)', $response->getHttpCode()), $response->getJson());
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Service not found') {
                throw new ClientException(sprintf('Mount point %s not present.', $mountPoint));
            }
            throw new ClientException($e->getMessage());
        }
    }

    /**
     * Build an array of url parameters.
     *
     * Parameter names found in $options are removed from the array.
     *
     * @param array $names  the names of the parameters to determin the value for
     * @param string $mountPoint Mount point of the foxx app to affect
     * @param array $options pass values to use here, if not present defaults will be used.
     *
     * @return array
     */
    protected static function buildParameterArray(array $names, string $mountPoint, array &$options)
    {
        $params = !empty($mountPoint) ? ['mount' => $mountPoint] : [];
        foreach ($names as $param) {
            if (isset($options[$param])) {
                $params[$param] = $options[$param];
                unset($options[$param]);
            } elseif (array_key_exists($param, static::FOXX_APP_DEFAULT_PARAMS)) {
                $params[$param] = self::FOXX_APP_DEFAULT_PARAMS[$param];
            } else {
                throw new ClientException("Unknown option '{$param}'.");
            }
        }

        return $params;
    }
}

class_alias(FoxxHandler::class, '\triagens\ArangoDb\FoxxHandler');
