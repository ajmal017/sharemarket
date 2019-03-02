<?php

namespace App\Http\Controllers;

use App\Imports\ShareImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Model\ShareInfo;

class ShareInfoController extends Controller
{
    public function import()
    {
        $file = fopen('LDE_EQUITIES_MORE_THAN_5_YEARS.csv', 'r+');
        // dd($file, fgetcsv($file));'
        $shareArray = $temp = $indexData = array();
        while (($line = fgetcsv($file)) !== false) {
            array_push($temp, $line);
        }

        $fno = $this->isFno();

        for ($j = 1; $j < count($temp); $j++) {
            $shareArray [$j]['company_name'] = $temp[$j][2];
            $shareArray [$j]['symbol'] = $temp[$j][0];
            $shareArray [$j]['isin'] = $temp[$j][1];
            $shareArray [$j]['created_at'] = date('Y-m-d H:i:s');
            $shareArray [$j]['updated_at'] = date('Y-m-d H:i:s');
            $indexData = $this->indexInfo($temp[$j][0]);
            $shareArray [$j]['sector_index'] = $indexData['sector'];
            $shareArray [$j]['stock_pe'] = $indexData['sectorPE'];
            $shareArray [$j]['index_pe'] = $indexData['PE'];
            $shareArray [$j]['fno'] = in_array($temp[$j][0], $fno['underlyings']) ? 'y' : 'n';
        }
        dd(ShareInfo::insert($shareArray));
        return redirect('/')->with('success', 'All good!');
    }

    public function indexInfo($symbol)
    {
        $context = stream_context_create(
            array(
                'http' => array(
                    'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
                    'timeout' => 10000
                ),
            )
        );
        $json = json_decode(file_get_contents("https://www.nseindia.com/live_market/dynaContent/live_watch/get_quote/getPEDetails.jsp?symbol=$symbol", false, $context), true);
        return $json;
    }

    public function isFno()
    {
        $context = stream_context_create(
            array(
                'http' => array(
                    'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
                    'timeout' => 10000
                ),
            )
        );
        $json = json_decode(file_get_contents("https://www.nseindia.com/live_market/dynaContent/live_watch/get_quote/ajaxFOGetQuoteDataTest.jsp?i=FUTSTK&u=infy", false, $context), true);

        return $json;
    }

    public function oiDetail()
    {
      $context = stream_context_create(
          array(
              'http' => array(
                  'header' => array('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'),
                  'timeout' => 10000
              ),
          )
      );
        $f = file_put_contents("my-zip.zip", fopen("https://www.nseindia.com/archives/nsccl/mwpl/combineoi_20022019.zip", 'r', 0, $context), LOCK_EX,$context);
        if(FALSE === $f)
            die("Couldn't write to file.");
        $zip = new \ZipArchive;
        $res = $zip->open('my-zip.zip');
        if ($res === TRUE) {
          $zip->extractTo('f:/wamp/www/stockproject/sharemarket/public/extract-here');
          $zip->close();
          dd($zip);
        } else {
          //
        }
    }

}