<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\ParticipantOI;
use DB;

class ParticipantController extends Controller
{
    public $sD;

    public function __construct()
    {
        $this->pOi = new ParticipantOI();
    }

    public function participantOIData()
    {
        $from = new \DateTime('2014-02-22 00:00:00');
        $to = new \DateTime('2019-03-09 00:00:00');

        for ($i = 0; $from != $to; $i++) {
            if (in_array($from->format('D'), ['Sat', 'Sun'])) {
                $from = $from->modify('+1 day');
            } else {
                $dateOfPOI = $from->format('d') . $from->format('m') . $from->format('Y');
                $dataPOI = $this->pOi->participantOIDataPull($dateOfPOI);
                $poiDataStructure = $this->pOi->tableDataStructure($dataPOI, $dateOfPOI);
              //  dd($poiDataStructure, $dataPOI);
                if ($poiDataStructure) {
                    $yn = false;
                    $yn = $this->pOi->insertData($poiDataStructure);
                    if ($yn) {
                        $oiDate = $from->format('Y-m-d');
                        DB::table('dateinsert_report')->insert(['report' => 3, 'date' => $oiDate]);
                    }
                }
                $from = $from->modify('+1 day');
            }
        }

        return "All Participant Open Interest done from $from->format('Y-m-d') to $to->format('Y-m-d')";

    }
}