<?php

/**
 * ArangoDB PHP client: admin document handler
 *
 * @package   triagens\ArangoDb
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
 * @package   triagens\ArangoDb
 * @since     1.2
 */

class AdminHandler extends
    Handler
{
    /**
     * details for server version
     */
    const OPTION_DETAILS = "details";

    /**
     * Get the server version
     *
     * This will throw if the version cannot be retrieved
     *
     * @param bool $details - True to get a more detailed response
     *
     * @throws Exception
     *
     * @return string - a string holding the ArangoDB version
     * @since 1.2
     */
    public function getServerVersion($details = false)
    {
        $url = Urls::URL_ADMIN_VERSION;

        if ($details) {
            $url = UrlHelper::appendParamsUrl($url, array('details' => true));
        }

        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        if ($details) {
            return $data;
        } else {
            return $data['version'];
        }
    }


    /**
     * Get the server time
     *
     * This will throw if the time cannot be retrieved
     *
     * @throws Exception
     *
     * @return double - a double holding the timestamp
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
     * This will throw if the log cannot be retrieved
     *
     * @throws Exception
     *
     * @param array $options - an array of options that define the result-set:
     *
     * <p>Options are :<br>
     * <li>'upto' - returns all log entries up to a log-level. Note that log-level must be one of:</li>
     * <p>
     * <li>fatal / 0</li>
     * <li>error / 1</li>
     * <li>warning / 2</li>
     * <li>info / 3</li>
     * <li>debug / 4</li>
     * </p>
     * <li>'level'  -  limits the log entries to the ones defined in level. Note that `level` and `upto` are mutably exclusive.</li>
     * <li>'offset' -  skip the first offset entries.</li>
     * <li>'size'   -  limit the number of returned log-entries to size.</li>
     * <li>'start'  -  Returns all log entries such that their log-entry identifier is greater or equal to lid.</li>
     * <li>'sort'   -  Sort the log-entries either ascending if direction is asc, or descending if it is desc according to their lid. Note that the lid imposes a chronological order.</li>
     * <li>'search' -  Only return the log-entries containing the text string...</li>
     * </p>
     *
     * @return array - an array holding the various attributes of a log: lid, level, timestamp, text and the total amount of log entries before pagination.
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
     * Flush the server's modules cache
     * The call triggers a flush of the modules cache on the server. See Modules Cache for details about this cache.
     *
     * This will throw if the modules cache cannot be flushed
     *
     * @throws Exception
     *
     * @return array
     * @since 1.2
     */
    public function flushServerModuleCache()
    {
        $this->getConnection()->POST(Urls::URL_ADMIN_MODULES_FLUSH, '');

        return true;
    }


    /**
     * Reload the server's routing information
     * The call triggers a reload of the routing information from the _routing collection
     *
     * This will throw if the routing cannot be reloaded
     *
     * @throws Exception
     *
     * @return array
     * @since 1.2
     */
    public function reloadServerRouting()
    {
        $this->getConnection()->POST(Urls::URL_ADMIN_ROUTING_RELOAD, '');

        return true;
    }


    /**
     * Get the server statistics
     * Returns the statistics information. The returned objects contains the statistics figures, grouped together
     * according to the description returned by _admin/statistics-description.
     * For instance, to access a figure userTime from the group system, you first select the sub-object
     * describing the group stored in system and in that sub-object the value for userTime is stored in the
     * attribute of the same name.In case of a distribution, the returned object contains the total count in count
     * and the distribution list in counts.
     * For more information on the statistics returned, please lookup the statistics interface description at
     *
     * @link  http://www.arangodb.org/manuals/1.3.devel/HttpSystem.html#HttpSystemAdminStatistics
     *
     * This will throw if the statistics cannot be retrieved
     *
     * @throws Exception
     *
     * @return array
     *
     * @see   getServerStatisticsDescription()
     *
     * @since 1.3
     */
    public function getServerStatistics()
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_ADMIN_STATISTICS, array());
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data;
    }


    /**
     * Returns a description of the statistics returned by getServerStatistics().
     * The returned objects contains a list of statistics groups in the attribute groups
     * and a list of statistics figures in the attribute figures.
     * For more information on the statistics returned, please lookup the statistics interface description at
     *
     * @link  http://www.arangodb.org/manuals/1.3.devel/HttpSystem.html#HttpSystemAdminStatistics
     *
     * This will throw if the statistics-description cannot be retrieved
     *
     * @throws Exception
     *
     * @param array $options - an array of options that define the result-set:
     *
     * <p>Options are :<br>
     * <li>'granularity' - use minutes for a granularity of minutes, hours for hours, and days for days. The default is minutes.</li>
     * <li>'figures' - a list of figures, comma-separated. Possible figures are httpConnections. You can use all to get all figures. The default is httpConnections.</li>
     * <li>'length' - If you want a time series, the maximal length of the series as integer. You can use all to get all available information. You can use current to get the latest interval.</li>
     *
     * @return array
     *
     * @see   getServerStatistics()
     *
     * @since 1.3
     */
    public function getServerStatisticsDescription($options = array())
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_ADMIN_STATISTICS_DESCRIPTION, $options);
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data;
    }
}
