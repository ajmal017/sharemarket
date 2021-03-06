<?php

namespace App\Console\Commands;

use App\Imports\ShareImport;
use Illuminate\Console\Command;
use App\Model\StockData;
use App\Model\ShareInfo;
use App\Model\ParticipantOI;
use App\Model\OptionData;
use App\Model\OpenInterest;
use App\Imports\CommonFunctionality;
use App\Model\OiSpurt;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StockDataUploader extends Command
{
    public $sd;
    public $si;
    public $po;
    public $od;
    public $sim;
    public $oi;
    public $os;
    public $shareImp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataupload';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All stock data uploaded successfully';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        ini_set("allow_url_fopen", 1);
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        parent::__construct();
        $this->po = new ParticipantOI();
        $this->sd = new StockData();
        $this->od = new OptionData();
        $this->oi = new OpenInterest();
        $this->si = new ShareInfo();
        $this->cf = new CommonFunctionality();
        $this->os = new OiSpurt();
        $this->shareImp = new ShareImport();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       // echo $this->oiSpurts();
        echo $this->bhavCopyDataPull();
        echo $this->openInterest();
        echo $this->participantOI();
        echo $this->delivery();
        echo $this->watchListBasedOnOI();
        $this->sd->getAvgDeliveryPerDay();
        //echo $this->optionChainData();
        return true;
    }

    public function openInterest()
    {
        $tableName = 'oi_data';
        $frmToDates = $this->cf->fromDateToDate($tableName);
        if ($frmToDates === false) {
            return "Open Interest data is already updated";
        } else {
            return $this->si->oiPullDates($frmToDates['fromDate'], $frmToDates['toDate']);
        }
    }

    public function participantOI()
    {
        $tableName = 'participant_oi';
        $frmToDates = $this->cf->fromDateToDate($tableName);
        if ($frmToDates === false) {
            return "Participant OI data is already updated";
        } else {
            return $this->po->participantOIData($frmToDates['fromDate'], $frmToDates['toDate']);
        }
    }

    /** report 2 = delivery; 3 = Paricipant OI; 4 = OI */
    public function delivery()
    {
        $tableName = 'stock_data';
        $frmToDates = $this->cf->fromDateToDate($tableName);

        if ($frmToDates === false) {
            return "delivery data is already updated";
        } else {
            return $this->sd->delivery($frmToDates['fromDate'], $frmToDates['toDate']);
        }
    }

    public function watchListBasedOnOI()
    {
        $watchlist = $this->oi->avgOIAsPerDayWatchlist();
        $count = count($watchlist);
        return "$count no of stocks added in watchlist\n";
    }

    public function oiSpurts()
    {
        $yn = $this->os->riseInPriceRiseInOI();
        $yn = $this->os->slideInPriceRiseInOI();
        if ($yn) {
            return "OI spurts data added\n";
        } else {
            return "OI spurts data already added\n";
        }
    }

    public function optionChainData()
    {
        $insertYN = true;
        $this->od->indexOptionData($insertYN);
        $this->od->stockOptionData($insertYN);
    }

    public function bhavCopyDataPull()
    {
        $data = $this->sd->bhavCopyDataPull();
        $bhavcopy = $this->shareImp->convertPlainTextLineByLineToArray($data);
        $bhavcopyData = $this->sd->stockDataStructure($bhavcopy);
        $yn = $this->sd->stockDataInsert($bhavcopyData);
        if ($yn) {
            return "Bhav copy data added\n";
        }
        switch ($yn) {
            case 0:
                echo "Bhav copy record already added\n";
                break;
            case 1:
                echo "Bhav copy data added successfully\n";
                break;
            case 2:
                echo "No data in Bhav copy\n";
                break;
            default:
                echo "Kuch to Gadbad he\n";
        }
    }
}
