<?php

/**
 * @author Justin Burns
 * @date 1/15/2014
 *
 *
 * Simple dummy/test CMA API stub for GET-ting the CMA "categories" or
 * POST-ting to the "notifications" endpoint.  No other endpoints are supported at this time
 *
 * For details on the CMA API,
 * see => https://reliance.atlassian.net/wiki/display/CMA/CMA+Service+API
 *
 * CMA categories endpoint: /apps/{appid}/categories
 * CMA notifications endpoint: {version}/apps/{appid}/notifications
 *
 * NOTE: CMA 'version' currently = 1
 *
 * Usage: append on (after *.php) the CMA endpoint:
 * Ex. https://sandbox.testschoolmessenger.com/kbhigh/_cma_api_stub.php/apps/2/categories; target endpoint = /apps/2/categories
 * Ex. https://sandbox.testschoolmessenger.com/kbhigh/_cma_api_stub.php/1/apps/2/notifications; target endpoint = /1/apps/2/notifications
 *
 *
 * Testing
 * 1) CMA Categories: you can use your browser or PHP's curl to do a simple GET for the categories.
 * ---------------------------------------------------
 * Ex. using your browser, simply enter something like this, with your specific host/customername, ex.
 * https://sandbox.testschoolmessenger.com/kbhigh/_cma_api_stub.php/apps/2/categories
 * ---------------------------------------------------
 * Ex. using curl, go to your VM's www dir, then enter something like this, with your specific host/customername, ex
 * [root@sandbox www]# curl -i -k -X GET https://sandbox.testschoolmessenger.com/kbhigh/_cma_api_stub.php/apps/2/categories
 *
 * the above command will yield a result like so:
 * HTTP/1.1 200 OK
 * Date: Wed, 15 Jan 2014 20:08:22 GMT
 * Server: Apache
 * Vary: Accept-Encoding
 * Content-Length: 271
 * Content-Type: application/json
 *
 * [{"id":0,"name":"School 0"},{"id":1,"name":"School 1"},{"id":2,"name":"School 2"},{"id":3,"name":"School 3"},{"id":4,"name":"School 4"},{"id":5,"name":"School 5"},{"id":6,"name":"School 6"},{"id":7,"name":"School 7"},{"id":8,"name":"School 8"},{"id":9,"name":"School 9"}]
 *
 * ------------------------------------------------------------------------------------------------
 *
 * 2) CMA notifications: you can use your browser (with add-on that supports POSTS) or PHP's curl to do a simple POST for the notifications.
 * ---------------------------------------------------
 * Ex. using your browser, check out FF add-on 'Poster' or others, or Chrome extension for POSTING
 * ---------------------------------------------------
 * Ex. using curl, go to your VM's www dir, then enter something like this, with your specific host/customername, ex
 * [root@sandbox www]# curl -i -k -X POST -d 'title=MyTitle&body=MyBod&categories=%5B1%2C2%2C3%5D'  https://sandbox.testschoolmessenger.com/kbhigh/_cma_api_stub.php/1/apps/234/notifications
 * where categories=%5B1%2C2%2C3%5D => categories=[1,2,3] encoded
 *
 * the above command will yield a result like so: (an empty '200' response)
 * HTTP/1.1 200 OK
 * Date: Wed, 15 Jan 2014 20:08:22 GMT
 * Server: Apache
 * Vary: Accept-Encoding
 * Content-Length: 0
 * Content-Type: text/html
 *
 *
 *
 */

    // ex. [PATH_INFO] => /apps/123/categories
    // this maps to our CMA API, ex categories endpoint
    $path = $_SERVER['PATH_INFO'];


    // @GET
    // @path /apps/{appId}/categories
    // if $path exists and has the format: /apps/{appId}/categories, then
    // return json response with dummy CMA categories above
    if ($path && preg_match('/^\/apps\/\d+\/categories$/', $path)) {
        // create some dummy/test CMA categories to be returned via json
        // the CMA categories response is an array of objects, ie [{"id":"1","name":"School A"},{"id":"2","name":"School B"}, ...]
        $cma_categories = array();
        for ($i = 0; $i < 10; $i += 1) {
            $cma_categories[] = (object) array('id' => $i, 'name' => 'School ' . $i );
        }

        header('Content-Type: application/json');
        echo json_encode($cma_categories);
        exit();

        // @POST
        // @path /{version}/apps/{appId}/notifications
        // if $path exists and has the format: /{version}/apps/{appId}/notifications and
        // the POST params exits, then return 200 (empty response)
    } else if ($path && preg_match('/^\/1\/apps\/\d+\/notifications$/', $path) && !empty($_POST)) {
                // do we want to check for all expected POST params (title, body, & categories)?
        //       isset($_POST['title']) && isset($_POST['body']) && isset($_POST['categories'])) {
        header("HTTP/1.1 200 OK");
        exit();

    }

    // else return 400; only above to endpoints supported at this time
    header("HTTP/1.1 400");
    exit();

?>