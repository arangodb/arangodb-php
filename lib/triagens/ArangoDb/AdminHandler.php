<?php

/**
 * ArangoDB PHP client: admin document handler
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * An admin handler that utilizes the ArangoDB's Admin API. It does so by issuing the
 * appropriate HTTP requests to the server.
 *
 * @package   ArangoDbPhpClient
 * @since     1.2
 */

class AdminHandler extends
    Handler
{
    /**
     * Get the server version
     *
     * @return string
     * @since 1.2
     */
    public function getServerVersion()
    {
        $response = $this->getConnection()->get(Urls::URL_ADMIN_VERSION);
        $data     = $response->getJson();

        return $data['version'];
    }


    /**
     * Get the server time
     *
     * @return integer
     * @since 1.2
     */
    public function getServerTime()
    {
        $response = $this->getConnection()->get(Urls::URL_ADMIN_TIME);
        $data     = $response->getJson();

        return $data['time'];
    }


    /**
     * Get the server log
     *
     * @param array $options - an array of options that define the resultset:
     *
     * <p>Options are :<br>
     * <li>'upto' - Returns all log entries upto a log-level. Note that log-level must be</li>
     * <p>Log-levels are :<br>
     * <li>fatal / 0</li>
     * <li>error / 1</li>
     * <li>warning / 2</li>
     * <li>info / 3</li>
     * <li>debug / 4</li>
     * </p>
     * <li>'level'  -  iReturns all log entries of log-level. Note that level= and upto= are mutably exclusive.</li>
     * <li>'offset' -  skip the first offset entries.</li>
     * <li>'size'   -  limit the number of returned log-entries to size.</li>
     * <li>'start'  -  Returns all log entries such that their log-entry identifier is greater or equal to lid.</li>
     * <li>'sort'   -  Sort the log-entries either ascending if direction is asc, or descending if it is desc according to their lid. Note that the lid imposes a chronological order.</li>
     * <li>'search' -  Only return the log-entries containing the text string...</li>
     * </p>
     *
     * @return array
     * @since 1.2
     */
    public function getServerLog($options = array())
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_ADMIN_LOG, $options);
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Get the server status
     * The call returns an object with thh attributes described here: http://www.arangodb.org/manuals/1.2.beta3/HttpSystem.html#HttpSystemStatus
     *
     * @return array
     * @since 1.2
     */
    public function getServerStatus()
    {
        $response = $this->getConnection()->get(Urls::URL_ADMIN_STATUS);
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Flush the server's modules cache
     * The call triggers a flush of the modules cache on the server. See Modules Cache for details about this cache.
     *
     * @return array
     * @since 1.2
     */
    public function flushServerModuleCache()
    {
        $response = $this->getConnection()->POST(Urls::URL_ADMIN_MODULES_FLUSH, '');
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Reload the server's routing information
     * The call triggers a reload of the routing information from the _routing collection
     *
     * @return array
     * @since 1.2
     */
    public function reloadServerRouting()
    {
        $response = $this->getConnection()->POST(Urls::URL_ADMIN_ROUTING_RELOAD, '');
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Get the server connection statistics
     * The call returns statistics about the current and past requests. The following parameter control which information is returned:
     *
     * @param array $options - an array of options that define the resultset:
     *
     * <p>Options are :<br>
     * <li>'granularity' - use minutes for a granularity of minutes, hours for hours, and days for days. The default is minutes.</li>
     * <li>'figures' - a list of figures, comma-separated. Possible figures are httpConnections. You can use all to get all figures. The default is httpConnections.</li>
     * <li>'length' - If you want a time series, the maximal length of the series as integer. You can use all to get all available information. You can use current to get the latest interval.</li>
     *
     * @return array
     * @since 1.2
     */
    public function getServerConnectionStatistics($options = array())
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_ADMIN_CONNECTION_STATISTICS, $options);
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Get the server request statistics
     * The call returns statistics about the current and past requests. The following parameter control which information is returned:
     *
     * @param array $options - an array of options that define the resultset:
     *
     * <p>Options are :<br>
     * <li>'granularity' - use minutes for a granularity of minutes, hours for hours, and days for days. The default is minutes.</li>
     * <li>'figures' - a list of figures, comma-separated. Possible figures are httpConnections. You can use all to get all figures. The default is httpConnections.</li>
     * <li>'length' - If you want a time series, the maximal length of the series as integer. You can use all to get all available information. You can use current to get the latest interval.</li>
     *
     * @return array
     * @since 1.2
     */
    public function getServerRequestStatistics($options = array())
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_ADMIN_REQUEST_STATISTICS, $options);
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data;
    }
}