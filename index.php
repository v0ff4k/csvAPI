<?php

// include FTP class
include "lib/FTP.Class.php";
require_once('lib/parsecsv.lib.php');

//configs

$remoteFolder = "out"; //without start or end slash
$remoteFile = "inventory.csv"; //file on remote

$timeFormat = "Y-m-d_H-i-s"; //date format and file name
$hours = 24; // how old files store in folders, in hours

$localFolder = "updated/"; //relative or abs path in which folder to store. with last slash !
$localFileSellerActive = $localFolder."SellerActive.csv"; // can be like $localFile



// set FTPC object
$ftp = new FTPC("ftp.server.com", "user", "pass");



///////////////
// functions //
///////////////
/**
 * Delete files in $folder if creation time in hours is too old.
 * @param $folder
 * @param int $hours
 */
function deleteOld($folder, $hours = 24){
    $folder = $folder."*";
    $files = glob($folder);
    foreach($files as $file){
        $lastModifiedTime = filemtime($file);
        $currentTime = time();
        $timeDiff = abs($currentTime - $lastModifiedTime)/( 60 * 60);
        if(is_file($file) && $timeDiff > $hours)
            unlink($file);
    }
}

/**
 * Transform into SellerActive file, using custom formulas
 * @param $data
 */
function processCSV($data){

    $dataSellerActive = array();

        foreach ($data as $value){

            // "My custom formulas"
            $itemCost =  $value['price'];
            $shippingCost = ($value['1stclass'] == 0) ? 7.95 : 2.61 ;
            $sellerCost = $itemCost + $shippingCost;
            $MAPPrice = $value['map'];
            $MRPPrice = $value['mrp'];
            $sellerCostPlusCommission = $sellerCost / 0.91;

            $newCustomSellerCost = ($MAPPrice > $sellerCostPlusCommission) ? $MAPPrice : $sellerCostPlusCommission ;
            // MAX(IF(ISBLANK(MAP Price),"0.00",MAP Price),(SellerCostplusCommission)) megaformula :)
            $maxPrice = $newCustomSellerCost * 1.25;

            // Import fields into SellerActive
            $dataSellerActive[] = array(
                'SellerSKU' => $value['sku'],
                'Cost' => round($sellerCost, 2),
                'Price (Preferred)' => round($maxPrice, 2),
                'Price (minimum)' => round($value['list'], 2),
                'Price (maximum)' => round($maxPrice, 2),
                'MAP Price' => round($MAPPrice, 2),
                'Price (retail)' =>  round($value['list'], 2)
            );

        }

    return $dataSellerActive;
}

/////////
// run //
/////////

// start connect to FTP server
if($ftp->connect()) {
    $ftp->cd($remoteFolder);
    $modifiedTime = $ftp->modifiedTime($remoteFile, $timeFormat);
    $localFile = $localFolder.$modifiedTime.".csv";//like 2017-02-08_13-56-00.csv

    if(!file_exists($localFile)) {

        //unlink all files in $localFolder
          //array_map('unlink', glob($localFolder."*"));
        // or delete only old files:
          deleteOld($localFolder, $hours);

        //download and write file
        if ($ftp->get($remoteFile, $localFile)) {
            //"File downloaded"

            # create new parseCSV object.
            $csv = new parseCSV();
            # Parse '***.csv' using automatic delimiter detection...
            $csv->auto($localFile);

            print "<br />import remote file for processing";

            # Output result.
            #echo "<pre>";
            #print_r($csv->data);

            # Transform into SellerActive using custom formulas
            $customFormula = processCSV($csv->data);

            # write
            $csvSellerActive = new parseCSV();
            $csvSellerActive->save($localFileSellerActive, $customFormula);

            print "<br />Write suceffily";

        } else {
            print "<br />Download failed: " . $ftp->error;
        }

    }else{

        print "<br />All up to date and fresh. No needs to update.";

    }


} else {
    // connection failed, display last error
    print "Connection failed: " . $ftp->error;
}
