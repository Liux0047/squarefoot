<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 3/7/2015
 * Time: 12:55 PM
 */
class HDBController extends BaseController
{

    const PAST_TRANSACTION = 1;
    const RENTAL_CONTRACT = 2;


    private $interval = 100;


    /*
    |--------------------------------------------------------------------------
    | Default Crawler Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

    public function getData($batch)
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 3000);
        //set POST variables
        $url = 'https://www.squarefoot.com.sg/trends-and-analysis/hdb?p=';
        //cookie file
        $cookieFile = public_path() . DIRECTORY_SEPARATOR . 'cookies.txt';

        $cUrls = array();

        $streetNames = StreetName::skip($batch * $this->interval)->take($this->interval)->get();

        //create the multi handler
        $multiHandler = curl_multi_init();

        foreach ($streetNames as $streetName) {
            //open connection
            $options = array(
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING => "",       // handle all encodings
                CURLOPT_USERAGENT => "spider", // who am i
                CURLOPT_AUTOREFERER => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                CURLOPT_TIMEOUT => 120,      // timeout on response
                CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
                CURLOPT_SSL_VERIFYPEER => false,
                //CURLOPT_COOKIEFILE => $cookieFile,
                //CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_COOKIE => '4c58ce487e2c2608deb11d56b844b0c1=164a0d0a4f26c39fd0dad9b1dda296f4; path=/; domain=www.squarefoot.com.sg; Secure',
                CURLOPT_POST => 1,
                //CURLOPT_POSTFIELDS => 'txtbox=' . $postalCode,
            );

            $requestUrl = $url . $this->streetNameParam($streetName->street_name);

            $ch = curl_init($requestUrl);
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multiHandler, $ch);


            $cUrlRecord['curl'] = $ch;
            $cUrlRecord['streetName'] = $streetName->street_name;
            $cUrls[] = $cUrlRecord;
        }

        $running = null;
        //execute the handles
        do {
            $status = curl_multi_exec($multiHandler, $running);
            // Check for errors
            if ($status > 0) {
                // Display error message
                echo "ERROR!\n " . curl_multi_strerror($status);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $running);

        foreach ($cUrls as $ch) {
            echo $this->extractData(curl_multi_getcontent($ch['curl']), $ch['streetName']);
            //$records[] = $this->extractData(curl_multi_getcontent($ch['curl']), $ch['series']);
            curl_multi_remove_handle($multiHandler, $ch['curl']);
        }

        curl_multi_close($multiHandler);

    }


    private function extractData($html, $streetName)
    {

        //find the <div> tag of Past Transactions
        $pastTransactionHeader = "<h3 class='report-title'>Past transactions</h3>";
        $pastTransactionHeaderPos = strpos($html, $pastTransactionHeader);
        $pastTransactionTableTag = "<table class='minimalist' width='100%'>";
        $pastTransactionStart = strpos($html, $pastTransactionTableTag, $pastTransactionHeaderPos);
        $pastTransactionEndTag = "</table>";
        $pastTransactionEnd = strpos($html, $pastTransactionEndTag, $pastTransactionStart);

        if ($pastTransactionStart) {
            $pastTransactionDiv = substr($html, $pastTransactionStart, $pastTransactionEnd - $pastTransactionStart);

            echo "<p>" . $streetName . ": PAST TRANSACTIONS</p>";
            $this->extractRoom($pastTransactionDiv, $streetName, CrawlerController::PAST_TRANSACTION);

        } else {
            echo "<p>" . $streetName . ": PAST TRANSACTIONS not found</p>";
        }


        //find the <div> tag of Rental Contract
        $rentalContractHeader = "<h3 class='report-title'>Rental Contracts</h3>";
        $rentalContractHeaderPos = strpos($html, $rentalContractHeader);
        $rentalContractTableTag = "<table class='minimalist' width='100%' align='right'>";
        $rentalContractStart = strpos($html, $rentalContractTableTag, $rentalContractHeaderPos);
        $rentalContractEndTag = "</table>";
        $rentalContractEnd = strpos($html, $rentalContractEndTag, $rentalContractStart);

        if ($rentalContractStart) {
            $rentalContractDiv = substr($html, $rentalContractStart, $rentalContractEnd - $rentalContractStart);

            echo "<p>" . $streetName . ": RENTAL CONTRACTS</p><hr>";
            $this->extractRoom($rentalContractDiv, $streetName, CrawlerController::RENTAL_CONTRACT);
        } else {
            echo "<p>" . $streetName . ": RENTAL CONTRACTS not found</p><hr>";
        }


    }


    private function extractRoom($table, $streetName, $type)
    {
        $DOM = new DOMDocument;
        $DOM->loadHTML($table);

        //get all <tr>
        $items = $DOM->getElementsByTagName('tr');

        //discard first <tr> which is the header
        for ($i = 1; $i < $items->length; $i++) {
            $this->recordData($items->item($i)->childNodes, $streetName, $type);
        }

    }


    private function recordData($nodes, $streetName, $type)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = $node->nodeValue;
            if (strlen(trim($nodeValue))) {
                $data[] = $nodeValue;
            }
        }

        if (!empty($data) && count($data) >= 5) {
            if ($type == CrawlerController::PAST_TRANSACTION) {
                $pastTransaction = new HDBTransaction;
                $pastTransaction->month = $data[0];
                $pastTransaction->room_type = $data[1];
                $pastTransaction->model = $data[2];
                $pastTransaction->storey_range = $data[3];
                $pastTransaction->floor_area = $data[4];
                $pastTransaction->price = $data[5];
                $pastTransaction->price_psm = $data[6];
                $pastTransaction->street_name = $streetName;
                $pastTransaction->save();
            } else if ($type == CrawlerController::RENTAL_CONTRACT) {
                $rental = new HDBContract;
                $rental->month = $data[0];
                $rental->room_type = $data[1];
                $rental->estimated_area = $data[2];
                $rental->monthly_rent = $data[3];
                $rental->monthly_rent_psm = $data[4];
                $rental->street_name = $streetName;
                $rental->save();
            }


        }

    }


    private function streetNameParam($streetName)
    {
        $str = str_replace("Road", "rd", $streetName);
        $str = str_replace("Drive", "dr", $str);
        $str = str_replace("Avenue", "ave", $str);
        $str = str_replace("Street", "st", $str);
        $str = str_replace("'", "", $str);
        $str = str_replace(" ", "-", $str);
        return $str;
    }


}