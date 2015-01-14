<?php

class ResidentialController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Default Home Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

    const TRANSACTION = 1;
    const RENTAL_CONTRACT = 2;

    private $infoFields = array(
        'Developer',
        'Architect',
        'Main Contractor',
        'Land Size (sqm)',
        'GFA (sqm)',
        'Plot Ratio (Incl. Bonus)',
        'Project Name',
        'Street Name',
        'Property Type',
        'Tenure',
        'District / Planning Area',
        'Completion',
        'Number of units',
        'Indicative price range / Average*',
        'Indicative rental range / Average*',
        'Implied rental yield',
        'Historical high',
        'Indicative average price from historical high',
        'Historical low',
        'Buyer profile by status#',
        'Buyer profile by purchaser address#',
    );


    public function getData()
    {
        //overrides the default PHP memory limit.
        ini_set('memory_limit', '-1');
        //increase execution limit
        ini_set('max_execution_time', 3000);
        //set POST variables
        $url = 'https://www.squarefoot.com.sg/trends-and-analysis/residential?p=';
        //cookie file
        $cookieFile = public_path() . DIRECTORY_SEPARATOR . 'cookies.txt';

        $condoNames = CondoName::where('condo_name_id', '>', '1012')->get();
        //$condoNames = CondoName::where('condo_name', '=', 'The Lakeshore')->get();

        foreach ($condoNames as $condoName) {
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

            //replace ' with nothing,  space with -
            $requestCondoName = str_replace(" ", "-", str_replace("'", "", $condoName->condo_name));

            $requestUrl = $url . $requestCondoName;

            $ch = curl_init($requestUrl);
            curl_setopt_array($ch, $options);

            $html = curl_exec($ch);
            curl_close($ch);

            //echo $html;
            $this->extractData($html, $condoName);
        }


    }


    private function extractData($html, $condoName)
    {

        //find the <div> tag of Project Information
        $projectInfoHeader = "<center><h3 class='report-title'>Project Information</h3></center>";
        $projectInfoHeaderPos = stripos($html, $projectInfoHeader);
        $projectInfoTableTag = "<table width='95%' class='minimalist'>";
        $projectInfoStart = stripos($html, $projectInfoTableTag, $projectInfoHeaderPos);
        $projectInfoEndTag = "</table>";
        $projectInfoEnd = stripos($html, $projectInfoEndTag, $projectInfoStart);

        if ($projectInfoStart) {
            $projectInfoTable = substr($html, $projectInfoStart, $projectInfoEnd - $projectInfoStart);

            echo "<h3>" . $condoName->condo_name . ": Project Info</h3><hr>";

            $this->extractProjectInfo($projectInfoTable, $condoName->condo_name_id);

            //find the <div> tag of Rental Contract
            $rentalContractHeader = "<h3 class='report-title'>Rental contracts</h3>";
            $rentalContractHeaderPos = stripos($html, $rentalContractHeader);
            $rentalContractTableTag = "<table width='100%' class='sortable minimalist'";
            $rentalContractStart = stripos($html, $rentalContractTableTag, $rentalContractHeaderPos);
            $rentalContractEndTag = "</table>";
            $rentalContractEnd = stripos($html, $rentalContractEndTag, $rentalContractStart);

            if ($rentalContractStart) {
                $rentalContractDiv = substr($html, $rentalContractStart, $rentalContractEnd - $rentalContractStart);

                echo "<h3>" . $condoName->condo_name . ": Rental contracts</h3><hr>";
                $this->extractTable($rentalContractDiv, $condoName->condo_name_id, ResidentialController::RENTAL_CONTRACT);
            }


            //find the <div> tag of HISTORICAL TRANSACTIONS
            $transactionHeader = "<h3 class='report-title'>Historical Transactions </h3>";
            $transactionHeaderPos = stripos($html, $transactionHeader);
            $transactionTableTag = "<table width='100%' class='minimalist sortable' ";
            $transactionStart = stripos($html, $transactionTableTag, $transactionHeaderPos);
            $transactionEndTag = "</table>";
            $transactionEnd = stripos($html, $transactionEndTag, $transactionStart);

            if ($transactionStart) {
                $transactionTable = substr($html, $transactionStart, $transactionEnd - $transactionStart);

                echo "<h3>" . $condoName->condo_name . ": Historical Transactions</h3><hr>";
                $this->extractTable($transactionTable, $condoName->condo_name_id, ResidentialController::TRANSACTION);
            }

        }

    }

    private function extractProjectInfo($table, $condoNameId)
    {
        $table = str_replace("&", "&amp;", $table);
        $DOM = new DOMDocument;
        $DOM->loadHTML($table);

        //get all <tr>
        $items = $DOM->getElementsByTagName('tr');

        //initialize data array
        $data = array();
        foreach ($this->infoFields as $field) {
            $data[$field] = '';
        }

        //record all tr entries
        for ($i = 0; $i < $items->length - 1; $i++) {
            $nodes = $items->item($i)->childNodes;
            $data[$nodes->item(0)->nodeValue] = $nodes->item(2)->nodeValue;
        }

        $info = new ProjectInfo;
        $info->developer = $data['Developer'];
        $info->architect = $data['Architect'];
        $info->main_contractor = $data['Main Contractor'];
        $info->land_size_sqm = $data['Land Size (sqm)'];
        $info->GFA_sqm = $data['GFA (sqm)'];
        $info->plot_ratio = $data['Plot Ratio (Incl. Bonus)'];
        $info->project_name = $data['Project Name'];
        $info->street_name = $data['Street Name'];
        $info->property_type = $data['Property Type'];
        $info->tenure = $data['Tenure'];
        $info->district_planning_area = $data['District / Planning Area'];
        $info->completion = $data['Completion'];
        $info->number_of_units = $data['Number of units'];
        $info->indicative_price_range_average = $data['Indicative price range / Average*'];
        $info->indicative_rental_range_average = $data['Indicative rental range / Average*'];
        $info->implied_rental_yield = $data['Implied rental yield'];
        $info->historical_high = $data['Historical high'];
        $info->indicative_average_historical_high = $data['Indicative average price from historical high'];
        $info->historical_low = $data['Historical low'];
        $info->buyer_profile_by_status = $data['Buyer profile by status#'];
        $info->buyer_profile_by_address = $data['Buyer profile by purchaser address#'];
        $info->condo_name_id = $condoNameId;
        $info->save();
    }

    private function extractTable($table, $condoNameId, $type)
    {
        libxml_use_internal_errors(true);
        $table = str_replace("&", "&amp;", $table);
        $DOM = new DOMDocument;
        $DOM->loadHTML($table);

        //get all <tr>
        $items = $DOM->getElementsByTagName('tr');

        //discard first <tr> which is the header
        for ($i = 1; $i < $items->length; $i++) {
            $this->recordData($items->item($i)->childNodes, $condoNameId, $type);
        }

        echo "Rental Contract: " . ($items->length - 1) . " entries <br>";
    }


    private function recordData($nodes, $condoNameId, $type)
    {
        $data = array();
        foreach ($nodes as $node) {
            $nodeValue = $node->nodeValue;
            if (strlen(trim($nodeValue))) {
                $data[] = $nodeValue;
            }
        }

        if (!empty($data) && count($data) >= 7) {
            if ($type == ResidentialController::RENTAL_CONTRACT) {
                $rental = new ResidentialRental;
                $rental->lease_start = $data[0];
                $rental->street = $data[1];
                $rental->type = $data[2];
                $rental->unit_size_sqft = $data[3];
                $rental->number_of_bedrooms = $data[4];
                $rental->monthly_rent = $data[5];
                $rental->monthly_rent_psf = $data[6];
                $rental->condo_name_id = $condoNameId;
                $rental->save();
            } else if ($type == ResidentialController::TRANSACTION) {
                $transaction = new ResidentialTransaction;
                $transaction->contract_date = $data[0];
                $transaction->address = $data[1];
                $transaction->type_of_sale = $data[2];
                $transaction->unit_area_sqft = $data[3];
                $transaction->type_of_area = $data[4];
                $transaction->price_psm = $data[5];
                $transaction->price = $data[6];
                $transaction->purchaser_address = $data[7];
                $transaction->condo_name_id = $condoNameId;
                $transaction->save();
            }

        }

    }

}
