<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ShareImport;
use App\Model\OptionData;

class OptionController extends Controller
{
    public $od;

    public function __construct()
    {
        $this->od = new OptionData();
    }

    public function optionDataFetch()
    {
        dd($this->od->fnoStocksExpiry());
        $optionType = ['OPTIDX', 'OPTSTK'];
        $symbol = 'NIFTY';
        $expiryDate = '25APR2019';
        $url = "https://www.nseindia.com/live_market/dynaContent/live_watch/option_chain/optionKeys.jsp?segmentLink=&instrument=$optionType[0]&symbol=$symbol&date=$expiryDate";
        //dd($url);
        $data = $this->od->optionDataFetch($url);
        dd($data);
    }

    public function stockOptionChain()
    {
        $underlyingExpiries = $this->od->stockOptionData();
    }

    public function indexOptionChain()
    {
        $underlyingExpiries = $this->od->indexOptionData();
    }

    public function jabraAction()
    {
        $action = $this->od->jabardastAction();
        $rawPremium = $this->od->latestPremiums();
        $latestPremium = $this->od->latestPremiumDataStructure($rawPremium);
        // dd($latestPremium[1597]);
        return view('jabraaction', compact('action', 'latestPremium'));
    }

    public function jabardastActionWatchlist(Request $req)
    {
        if($req->stockName) {
            $action = $this->od->jabardastActionWatchlist($req->stockName);
            $rawPremium = $this->od->latestPremiums();
            $latestPremium = $this->od->latestPremiumDataStructure($rawPremium);
        } else {
            $action = $this->od->jabardastActionWatchlist($req->stockName);
            $rawPremium = $this->od->latestPremiums();
            $latestPremium = $this->od->latestPremiumDataStructure($rawPremium);
        }

        return view('jabraaction', compact('action', 'latestPremium'));
    }

    public function moreThanHundredIV()
    {
        $action = $this->od->moreThanHundredIV();
        $rawPremium = $this->od->latestPremiums();
        $latestPremium = $this->od->latestPremiumDataStructure($rawPremium);
        return view('jabraaction', compact('action', 'latestPremium'));
    }

    public function jabraIV()
    {
        $title = "Action in IV's";
        $action = $this->od->jabardastIV();
        return view('jabraIV', compact('action', 'title'));
    }

    public function niftyExpiryWise($expiry)
    {
        $title = "Nifty Heavy OI Positions";
        $action = $this->od->niftyExpiryWise($expiry);
        return view('jabraIV', compact('action', 'title'));
    }
}
