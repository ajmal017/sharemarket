<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Imports\ShareImport;
use App\Imports\CommonFunctionality;

class StockData extends Model
{
    public $shareImp;
    public $context;
    protected $table = 'stock_data';

    public function __construct()
    {
        $this->shareImp = new ShareImport();
        $this->context = $this->shareImp->contextValue();
    }

    public function shareDataPull()
    {
        $url = "https://www.nseindia.com/products/dynaContent/common/productsSymbolMapping.jsp?symbol=infy&segmentLink=3&symbolCount=1&series=ALL&dateRange=24month&fromDate=&toDate=&dataType=PRICEVOLUMEDELIVERABLE";
        $url = "https://www.nseindia.com/archives/equities/mto/MTO_01032019.DAT";
        $html = $this->shareImp->get($url);
        dd($html);
        $nodes = $this->shareImp->findId('csvFileName');//  csvContentDiv

        dd($html, $nodes);
        foreach ($nodes as $tag) {
            $tag_value[] = explode("\n", $tag->nodeValue);
        }

        dd($tag_value);
        $json = json_decode(file_get_contents("https://www.nseindia.com/products/dynaContent/common/productsSymbolMapping.jsp?symbol=infy&segmentLink=3&symbolCount=1&series=ALL&dateRange=24month&fromDate=&toDate=&dataType=PRICEVOLUMEDELIVERABLE", false, $this->context), true);
        //dd($json);
    }

    public function shareOHLC()
    {
        $from = new DateTime('2018-10-04');
        $to = new DateTime('2019-10-04');
        if (in_array($from->format('D'), ['Sat', 'Sun'])) {
            $from = $from->modify('+1 day');
        } else {
            $dateOfDelivery = $from->format('d') . $from->format('m') . $from->format('Y');
            $url = "https://www.nseindia.com/content/historical/EQUITIES/2019/MAR/cm01MAR2019bhav.csv.zip";
            $html = $this->shareImp->downloadZip($url);

            $html = $this->shareImp->get($url);
        }
    }

    public function delivery($from, $to)
    {
        $frm = $from->format('Y-m-d');
        for ($i = 0; $from <= $to; $i++) {
            if (in_array($from->format('D'), ['Sat', 'Sun'])) {
                $from = $from->modify('+1 day');
            } else {
                $dateOfDelivery = $from->format('d') . $from->format('m') . $from->format('Y');
                $dataDelivery = $this->deliveryPull($dateOfDelivery);

                if ($dataDelivery) {
                    $yn = false;
                    $yn = $this->insertData($dataDelivery);
                    if ($yn) {
                        $DelDate = $from->format('Y-m-d');
                        \DB::table('dateinsert_report')->insert(['report' => 2, 'date' => $DelDate]);
                    }
                }
                $from = $from->modify('+1 day');
            }
        }
        $to = $to->format('Y-m-d');

        return "All delivery done from $frm to $to\n";
    }

    public function deliveryPull($date)
    {
        $file = @file_get_contents("https://www.nseindia.com/archives/equities/mto/MTO_$date.DAT", false, $this->context);
        if ($file) {
            $convert = explode("\n", $file); //create array separate by new line
            foreach ($convert as $value) {
                $shareArray[] = explode(",", $value);
            }
            $j = 0;
            for ($i = 4; $i < count($shareArray) - 1; $i++) {
                if (count($shareArray) > 0 && isset($shareArray[$i][2]) && 'eq' === strtolower($shareArray[$i][3])) {
                    $dataDelivery[$j]['symbol'] = $shareArray[$i][2] ?? null;
                    $dataDelivery[$j]['series'] = $shareArray[$i][3] ?? null;
                    $dataDelivery[$j]['total_traded_qty'] = $shareArray[$i][4] ?? null;
                    $dataDelivery[$j]['deliverable_qty'] = $shareArray[$i][5] ?? null;
                    $dataDelivery[$j]['per_delqty_to_trdqty'] = $shareArray[$i][6] ?? null;
                    $dataDelivery[$j]['date'] = "$date[4]$date[5]$date[6]$date[7]-$date[2]$date[3]-$date[0]$date[1]";
                    $j++;
                }
            }
            return $dataDelivery;
        } else {
            return false;
        }
    }

    public function insertData(array $dataDelivery)
    {
        return StockData::insert($dataDelivery);
    }

    public function bhavCopyDataPull()
    {
        $url = 'https://www.nseindia.com/products/content/sec_bhavdata_full.csv';
        return $this->shareImp->pullDataFromRemote($url);
    }

    public function stockDataStructure($shareArray)
    {
        $j = 0;
        $dataDelivery = [];
        for ($i = 1; $i < count($shareArray) - 1; $i++) {
            if (count($shareArray) > 0 && isset($shareArray[$i][0]) && 'eq' === strtolower(trim($shareArray[$i][1]))) {
                $cm = new CommonFunctionality();
                $bhavDaate = $cm->convertExpiryToDateFormat(trim($shareArray[$i][2]));
                $dataDelivery[$j]['symbol'] = trim($shareArray[$i][0]) ?? null;
                $dataDelivery[$j]['series'] = $shareArray[$i][1] ?? null;
                $dataDelivery[$j]['prev_close'] = trim($shareArray[$i][3]) ?? null;
                $dataDelivery[$j]['open'] = trim($shareArray[$i][4]) ?? null;
                $dataDelivery[$j]['high'] = trim($shareArray[$i][5]) ?? null;
                $dataDelivery[$j]['low'] = trim($shareArray[$i][6]) ?? null;
                $dataDelivery[$j]['last_price'] = trim($shareArray[$i][7]) ?? null;
                $dataDelivery[$j]['close'] = trim($shareArray[$i][8]) ?? null;
                $dataDelivery[$j]['vwap'] = trim($shareArray[$i][9]) ?? null;
                $dataDelivery[$j]['total_traded_qty'] = trim($shareArray[$i][10]) ?? null;
                $dataDelivery[$j]['turnover'] = trim($shareArray[$i][11]) ?? null;
                $dataDelivery[$j]['no_of_trades'] = trim($shareArray[$i][12]) ?? null;
                $dataDelivery[$j]['deliverable_qty'] = trim($shareArray[$i][13]) ?? null;
                $dataDelivery[$j]['per_delqty_to_trdqty'] = trim($shareArray[$i][14]) ?? null;
                $dataDelivery[$j]['date'] = $bhavDaate;
                $j++;
            }
        }
        return $dataDelivery;
    }

    public function stockDataInsert(array $dataDelivery)
    {
        if (isset($dataDelivery[0]['date'])) {
            $fdResult = \DB::table('stock_price')->latest('date')->first();
            if (isset($fdResult->date) && $fdResult->date == $dataDelivery[0]['date']) {
                return false;
            } else {
                return \DB::table('stock_price')->insert($dataDelivery);
            }
        } else {
            return 2;
        }
    }
}
