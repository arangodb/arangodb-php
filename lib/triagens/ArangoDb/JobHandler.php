<?php

/**
 * ArangoDB PHP client: endpoint
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Job specification
 *
 * A job is a arango db job which is created by calling arango api with the x-arango-async:store header
 * These jobs will be executed asynchronously. This class enables the user to get the jobs result, delete them or get
 * all pending or done jobs from arango.
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class JobHandler
{

    /**
     * Returns the result of an async job
     *
     * Returns the result of an async job identified by job-id. If the async job result is present on the server,
     * the result will be removed from the list of result.
     * That means this method can be called for each job-id once.
     * The method will return the original job result's headers and body,
     * plus the additional HTTP header x-arango-async-job-id. If this header is present, then the job was found
     * and the response contains the original job's result. If the header is not present, the job was not found
     * and the response contains status information from the job amanger.
     * @param Connection $connection - the connection to be used
     * @param string     $jobId   - the Job ID
     *                               *
     *
     * @return array $responseArray - The response array.
     */
    public static function getJobResult(Connection $connection, $jobId)
    {
        $url    = UrlHelper::buildUrl(Urls::URL_DOCUMENT, array($jobId));
        $response = $connection->put($url, '');

        $responseArray = $response->getJson();

        return $responseArray;
    }




    /**
     * Deletes jobs
     *
     * Deletes either all job results, expired job results, or the result of a specific job.
     * Clients can use this method to perform an eventual garbage collection of job results.
     *
     * @param Connection $connection - the connection to be used
     * @param string     $type   - The type of jobs to delete. The type can be either done, all, pending or expired.
     * @param int        $timestamp  - A UNIX timestamp specifying the expiration threshold when type is expired.
     *                               *
     *
     * @return array $responseArray - The response array.
     */
    public static function deleteJobsByType(Connection $connection, $type, $timestamp = null)
    {
        $url    = UrlHelper::buildUrl(Urls::URL_DOCUMENT, array($type));
        if ($timestamp && $type == "expired") {
            $url    = UrlHelper::appendParamsUrl($url, array("stamp" => $timestamp));
        }
        $response = $connection->delete($url, '');

        $responseArray = $response->getJson();

        return $responseArray;
    }

    /**
     * Deletes a job
     *
     * Deletes a job specified by id
     *
     * @param Connection $connection - the connection to be used
     * @param string     $jobId   - The id of a job to delete
     *
     * @return array $responseArray - The response array.
     */
    public static function deleteJobsById(Connection $connection, $jobId)
    {
        $url    = UrlHelper::buildUrl(Urls::URL_DOCUMENT, array($jobId));

        $response = $connection->delete($url);

        $responseArray = $response->getJson();

        return $responseArray;
    }

    /**
     * Returns the list of ids of async jobs
     *
     * Returns the list of ids of async jobs with a specific status (either done or pending).
     * The list can be used by the client to get an overview of the job system status and to
     * retrieve completed job results later.
     * @param Connection $connection - the connection to be used
     * @param string     $type       - The type of jobs to return. The type can be either done or pending.
     *                               *
     *
     * @return array $responseArray - The response array.
     */
    public static function getAllJobsByState(Connection $connection, $type)
    {
        $url    = UrlHelper::buildUrl(Urls::URL_DOCUMENT, array($type));
        $response = $connection->get($url);

        $responseArray = $response->getJson();

        return $responseArray;
    }

}
