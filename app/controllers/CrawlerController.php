<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 10/30/2014
 * Time: 2:25 PM
 */
class CrawlerController extends BaseController
{

    const PAST_TRANSACTION = 1;
    const RENTAL_CONTRACT = 2;

    // 20 * 500
    private $seriesStart = 0;     //0
    private $seriesEnd = 800;    //actually 800
    private $seriesInterval = 100;

    private $HDB = array(
        'Ang Mo Kio',
        'Bedok',
        'Bishan',
        'Bukit Batok',
        'Bukit Merah',
        'Bukit Panjang',
        'Bukit Timah',
        'Central Area',
        'Choa Chu Kang',
        'Clementi',
        'Geylang',
        'Hougang',
        'Jurong East',
        'Jurong West',
        'Kallang%2FWhampoa',
        'Marine Parade',
        'Pasir Ris',
        'Punggol',
        'Queenstown',
        'Sembawang',
        'Sengkang',
        'Serangoon',
        'Tampines',
        'Toa Payoh',
        'Woodlands',
        'Yishun',
    );


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

    public function getData()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 3000);
        //set POST variables
        $url = 'https://www.squarefoot.com.sg/trends-and-analysis/hdb?a=';
        //cookie file
        $cookieFile = public_path() . DIRECTORY_SEPARATOR . 'cookies.txt';

        for ($HDBCount = 0; $HDBCount < count($this->HDB); $HDBCount++) {
            $cUrls = array();

            //create the multi handler
            $multiHandler = curl_multi_init();

            for ($series = $this->seriesStart; $series < $this->seriesEnd; $series += $this->seriesInterval) {
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
                    CURLOPT_COOKIE => '4c58ce487e2c2608deb11d56b844b0c1=ceb79222f5bd7ef7a9836ab749a16c88; path=/; domain=www.squarefoot.com.sg; Secure',
                    CURLOPT_POST => 1,
                    //CURLOPT_POSTFIELDS => 'txtbox=' . $postalCode,
                );

                $requestUrl = $url . $series . "-" . ($series + $this->seriesInterval) . "+" . $this->HDBParam($this->HDB[$HDBCount]);

                $ch = curl_init($requestUrl);
                curl_setopt_array($ch, $options);
                curl_multi_add_handle($multiHandler, $ch);


                $cUrlRecord['curl'] = $ch;
                $cUrlRecord['series'] = $series;
                $cUrlRecord['HDBName'] = $this->HDB[$HDBCount];
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
                echo $this->extractData(curl_multi_getcontent($ch['curl']), $ch['series'], $cUrlRecord['HDBName']);
                //$records[] = $this->extractData(curl_multi_getcontent($ch['curl']), $ch['series']);
                curl_multi_remove_handle($multiHandler, $ch['curl']);
            }

            curl_multi_close($multiHandler);

        }


    }


    private function extractData($html, $series, $HDBName)
    {

        //find the <div> tag of Past Transactions
        $pastTransactionHeader = "<h1 class='ja-typo-title'>Past Transactions</h1>";
        $pastTransactionHeaderPos = strpos($html, $pastTransactionHeader);
        $pastTransactionTableTag = "<div class='ja-typo-blockrow cols-1 clearfix'>";
        $pastTransactionStart = strpos($html, $pastTransactionTableTag, $pastTransactionHeaderPos);
        $pastTransactionEndTag = "<div class='ja-typo'>";
        $pastTransactionEnd = strpos($html, $pastTransactionEndTag, $pastTransactionStart);

        if ($pastTransactionStart) {
            $pastTransactionDiv = substr($html, $pastTransactionStart, $pastTransactionEnd - $pastTransactionStart);

            echo "<h3>" . $HDBName . " - " . $series . ": PAST TRANSACTIONS</h3><hr>";
            $this->extractDiv($pastTransactionDiv, CrawlerController::PAST_TRANSACTION);

        }


        //find the <div> tag of Rental Contract
        $rentalContractHeader = "<h1 class='ja-typo-title'>Rental Contracts</h1>";
        $rentalContractHeaderPos = strpos($html, $rentalContractHeader);
        $rentalContractTableTag = "<div class='ja-typo-blockrow cols-1 clearfix'>";
        $rentalContractStart = strpos($html, $rentalContractTableTag, $rentalContractHeaderPos);
        $rentalContractEndTag = "<div class='ja-typo'>";
        $rentalContractEnd = strpos($html, $rentalContractEndTag, $rentalContractStart);

        if ($rentalContractStart) {
            $rentalContractDiv = substr($html, $rentalContractStart, $rentalContractEnd - $rentalContractStart);

            echo "<h3>" . $HDBName . " - " . $series . ": RENTAL CONTRACTS</h3><hr>";
            $this->extractDiv($rentalContractDiv, CrawlerController::RENTAL_CONTRACT);
        }


    }

    private function extractDiv($div, $type)
    {

        $tableEnd = 0;
        for ($count = 1; $count <= 6; $count++) {
            //6 for executive
            if ($count == 6) {
                $roomTag = "<h3 class='report-title' style='margin-bottom:5px'>Executive</h3>";
            } else {
                $roomTag = "<h3 class='report-title' style='margin-bottom:5px'>" . $count . "-room</h3>";
            }

            $roomTagPos = stripos($div, $roomTag, $tableEnd);
            if ($roomTagPos) {
                $tableStart = strpos($div, "<table class='minimalist sortable' width='100%'", $roomTagPos);
                $tableEnd = strpos($div, "</table>", $tableStart);


                $this->extractRoom(substr($div, $tableStart, $tableEnd - $tableStart), $count, $type);

            }

        }


    }

    private function extractRoom($table, $roomCount, $type)
    {
        $DOM = new DOMDocument;
        $DOM->loadHTML($table);

        //get all <tr>
        $items = $DOM->getElementsByTagName('tr');

        //discard first <tr> which is the header
        for ($i = 1; $i < $items->length; $i++) {
            $this->recordData($items->item($i)->childNodes, $roomCount, $type);
        }

        echo $roomCount . ": " . ($items->length - 1) . " entries <br>";
    }


    private function recordData($nodes, $roomCount, $type)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = $node->nodeValue;
            if (strlen(trim($nodeValue))) {
                $data[] = $nodeValue;
            }
        }

        if (!empty($data) && count($data) >= 6) {
            if ($type == CrawlerController::PAST_TRANSACTION) {
                $pastTransaction = new PastTransaction;
                $pastTransaction->month = $data[0];
                $pastTransaction->block = $data[1];
                $pastTransaction->model = $data[2];
                $pastTransaction->lease_start_year = $data[3];
                $pastTransaction->age_years = $data[4];
                $pastTransaction->storey_range = $data[5];
                $pastTransaction->floor_area_sqm = $data[6];
                $pastTransaction->price = $data[7];
                $pastTransaction->price_psm = $data[8];
                $pastTransaction->room_count = $roomCount;
                $pastTransaction->save();
            } else if ($type == CrawlerController::RENTAL_CONTRACT) {
                $rental = new RentalContract;
                $rental->month = $data[0];
                $rental->block = $data[1];
                $rental->lease_start_year = $data[2];
                $rental->age_years = $data[3];
                $rental->estimated_area_sqm = $data[4];
                $rental->monthly_rent = $data[5];
                $rental->monthly_rent_psm = $data[6];
                $rental->room_count = $roomCount;
                $rental->save();
            }


        }

    }

    private function HDBParam($HDBName)
    {
        return str_replace(" ", "+", $HDBName);
    }


}