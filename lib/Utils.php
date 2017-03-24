<?php
/**
 * A library of common utility methods and functions.
 */

class Utils
{
    /**
     * Converts a number into a more currency apealing format. This method makes
     * use of the number_format() function which comes as part of the php
     * standard library.
     *
     * @param float $number
     * @return string
     */
    public static function currency($number)
    {
        return number_format(Common::round($number,2),2,'.',',');
    }

    public static function deCommalize($number)
    {
        return str_replace(',', '', $number);
    }
    
    public static function getUserInfo()
    {
        // Check SISF agency
        $marketingOfficerModel = Model::load('sisf.setup.marketing_officers_info');
        $officer = reset($marketingOfficerModel->getWithField2('user_id', $_SESSION['user_id']));
        
        if($officer === false)
        {
            $marketingOfficerModel = Model::load('ahonya.setup.agents_info');
            $officer = reset($marketingOfficerModel->getWithField2('user_id', $_SESSION['user_id']));            
            $productNamespace = 'ahonya';
            $userCodePrefix = 'N-RIA/';
            $agentIdentifier = 'agent_id';
            $kyc = 'kyc.clients';
            $shellClients = 'client';
            $accountNumber = 'client_code';
        }
        else
        {
            $productNamespace = 'sisf';
            $userCodePrefix = '';
            $agentIdentifier = 'marketing_officer_id';
            $kyc = 'sisf.liabilities.setup.contributors_data';
            $shellClients = 'contributor';
            $accountNumber = 'account_number';
        }
        
        return array(
            'user_name' => $_SESSION['user_name'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'user_id' => $_SESSION['user_id'],
            'agent' => $officer,
            'namespace' => $productNamespace,
            'code_prefix' => $userCodePrefix,
            'agent_identifier' => $agentIdentifier,
            'kyc' => $kyc,
            'shell_clients_model' => $shellClients,
            'account_number' => $accountNumber
        );
    }

    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into a unix timestamp. The conversion is done with the strtotime()
     * function which comes as part of the php standard library.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @return int
     */
    public static function stringToTime($string, $hasTime = false)
    {
        if(preg_match("/(\d{2})\/(\d{2})\/(\d{4})(\w\d{2}:\d{2}:\d{2})?/", $string) == 0) return false;
        $dateComponents = explode(" ", $string);

        $decomposeDate = explode("/", $dateComponents[0]);
        $decomposeTime = array();

        if($hasTime === true)
        {
            $decomposeTime = explode(":", $dateComponents[1]);
        }

        return
        strtotime("{$decomposeDate[2]}-{$decomposeDate[1]}-{$decomposeDate[0]}") +
        ($hasTime === true ? ($decomposeTime[0] * 3600 + $decomposeTime[1] * 60 + $decomposeTime[2]) : 0);
    }

    /**
     * Converts a string time representation of the format DD/MM/YYY [HH:MI:SS]
     * into an oracle date format DD-MON-YY.
     *
     * @param string $string The date
     * @param boolean $hasTime When specified, the time components are also added
     * @todo Allow the returning of the time values too.
     * @return string
     */
    public static function stringToDatabaseDate($string, $hasTime = false)
    {
        $timestamp = Common::stringToTime($string, $hasTime);
        return date("Y-m-d", $timestamp);
    }

    /**
     * Gives the amount in words of a number in terms of currencies. Hence
     * passing a value of 1250.20 into this function would generate something
     * like "One-Thousand, Two-Hundred and Fifty Ghana Cedis, Twenty Pesewas.
     *
     * @param double $number The number to be converted.
     * @todo Make it possible for this method to be called in different currencies.
     */
    public static function getCurrencyString($number)
    {
        $numbers = explode(".", $number);
        $wholePart = $numbers[0];
        //$fractionPart = round($numbers[1]/pow(10,strlen($numbers[1]))*100);
        $fractionPart = round(bcmul(bcdiv($numbers[1], bcpow(10, strlen($numbers[1]))), 100));//$numbers[1]/pow(10,strlen($numbers[1]))*100);
        return ucwords(Common::convert_number($wholePart) . " Ghana Cedis, " . Common::convert_number($fractionPart) . " Pesewas");
    }

    public static function drawChequeText($pdf,$x, $y, $text, $w = 0, $h = 0)
    {
        $pdf->SetXY($y - 3, 259 - $x);
        $pdf->Rotate(90);
        $pdf->WrapCell($w, $h, $text);
        $pdf->Rotate(0);
    }

    public static function drawPaymentVoucher($id, $field = "pv_id", $tableData = array())
    {
        $model = Model::load("brokerage.accounting.payment_vouchers");
        $model->queryResolve = false;
        $voucherData = $model->getWithField($field, $id);

        $model = Model::load("brokerage.accounting.cheques");
        $model->queryResolve = false;
        $chequeData = $model->getWithField("pv_id", $voucherData[0]["pv_id"]);

        $model = Model::load("brokerage.transaction.share_account_transaction");
        $transactionData = $model->get(array("conditions"=>"account_transaction_id = {$voucherData[0]["account_transaction_id"]}"), Model::MODE_ASSOC, false, false);

        $model = Model::load("brokerage.setup.clients");
        $clientData = $model->get(array("conditions"=>"client_id = {$voucherData[0]["client_id"]}"), Model::MODE_ASSOC, false, false);
        $client = trim("{$clientData[0]["surname"]} {$clientData[0]["first_name"]} {$clientData[0]["other_names"]} {$clientData[0]["company_name"]}");

        $usersModel = Model::load("system.users");
        $user = $usersModel[$voucherData[0]["user_id"]];

        $document = new PDFReport("P","A4");
        $document->add(new SecuritiesLogo());

        $header = new TextContent("Payment Voucher");
        $header->style["bold"] = true;
        $header->style["size"] = 16;

        $document->add($header);
        $document->add(new TextContent(date("d/m/Y", $voucherData[0]["voucher_date"]),array("size"=>12,"align"=>"R")));

        $totalValue = $transactionData[0]["amount"];
        $fractionPart = Common::currency($totalValue - floor($totalValue));
        $wholePart = $totalValue;

        $attribs1 = array
        (
            array("Payment Voucher Number",$voucherData[0]["pv_number"]),
            array("Cheque Number",$voucherData[0]["cheque_number"]),
            array("","")
        );

        $attribs2 = array
        (
            array("Paid To", $voucherData[0]['payee'] . $client),
            array("The Sum Of", Common::getCurrencyString($totalValue)), //Common::convert_number($wholePart)." Ghana cedis and ".Common::convert_number($fractionPart*100)." pesewas"),
            array("In Respect Of",$voucherData[0]["narration"]),
            array("Amount",common::currency($totalValue)),
            array("Issued by", $user[0]["last_name"]." ".$user[0]["first_name"]),
            array("",""),
        );

        $document->add(new TextContent(""));
        $document->add(new AttributeBox($attribs1));
        $document->add(new AttributeBox($attribs2));
        $document->add(new TextContent("I hereby certify the above particulars to be correct for payment.",array("size"=>10)));
        $document->add(new TextContent("Approved and passed out for payment" . str_repeat(" ", 36) . ".........................................................",array("size"=>10,"top_margin"=>10)));
        $document->add(new TextContent(str_repeat(" ",121) . "Finance Manager",array("size"=>8)));
        $document->add(new TextContent("Cheque Signed by                                                                   Cheque Signed by",array("size"=>10, "top_margin" => 10)));
        $document->add(new TextContent(".....................................................                                           .........................................................     ",array("size"=>10, "top_margin"=>5, "bottom_margin"=>10)));
        $table = new TableContent(
            array("A/C NO","DESCRIPTION","DEBIT(GHC)","CREDIT(GHC)"),
            count($tableData) == 0 ?
                array(
                    array("1509","Fidelity Bank","",Common::currency($totalValue)),
                    array($clientData[0]["gl_account_number"],$client,Common::currency($totalValue),"")
                )
                :
                $tableData
        );

        $document->add(new TextContent("Cheque Received By                                          Signature                                             Date",array("size"=>10, "top_margin" => 7)));
        $document->add(new TextContent("....................................                                       ...........................................                 .........................................",array("size"=>10, "top_margin"=>5, "bottom_margin"=>10)));

        $document->add($table);
        $table->style["decoration"] = true;

        $document->output();
        die();
    }

    public static function drawCheque($formatId, $chequeData)
    {
        
        $format = Model::load('system.cheque_formats')->getWithField('cheque_format_id', $formatId);
        $format = json_decode($format[0]['data'], true);
        
        $pdf = new PDFDocument("P", array($format['width'], $format['height']));
        $pdf->showFooter = false;
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Helvetica', null, 8);

        Common::drawChequeText($pdf, $format['stub_issue_date_x'], $format['stub_issue_date_y'], date("jS F, Y", $chequeData["issue_date"]));
        Common::drawChequeText($pdf, $format['stub_payee_x'], $format['stub_payee_y'], $chequeData["payee"]);

        Common::drawChequeText($pdf, $format['stub_narration_x'], $format['stub_narration_y'], wordwrap($chequeData["narration"], $format['narration_width']));

        $pdf->SetFont('Helvetica', 'B', 12);
        Common::drawChequeText($pdf, $format['stub_amount_x'], $format['stub_amount_y'], "***".number_format($chequeData["amount"], 2, "." ,",")."***", 100);

        $pdf->SetFont('Helvetica', null, 14);
        Common::drawChequeText($pdf, $format['payee_x'], $format['payee_y'], $chequeData["payee"], 100);

        $pdf->SetFont('Helvetica', null, 12);
        Common::drawChequeText($pdf, $format['words_x'], $format['words_y'], wordwrap(Common::getCurrencyString($chequeData["amount"]) . "******", $format['words_width']), 100);

        if($cheque[0]["reversed"]=="1")
        {
            $pdf->SetFont('Helvetica', 'B', 12);
            Common::drawChequeText($pdf, 80, 73, "*** Reversed Cheque ***", 100);
        }

        $pdf->SetFont('Helvetica', 'B', 14);
        Common::drawChequeText($pdf, $format['amount_x'], $format['amount_y'], "***".number_format($chequeData["amount"], 2, ".", ",")."***");

        $pdf->SetFont('Helvetica', null, 12);
        Common::drawChequeText($pdf, $format['issue_date_day_x'], $format['issue_date_day_y'], date("jS", $chequeData["issue_date"]));
        Common::drawChequeText($pdf, $format['issue_date_month_x'], $format['issue_date_month_y'], date("F", $chequeData["issue_date"]));
        Common::drawChequeText($pdf, $format['issue_date_year_x'], $format['issue_date_year_y'], date("Y", $chequeData["issue_date"]));

        $pdf->Output();
        die();   
    }


    /**
     * Generates a textual representation of a number by converting the value
     * of the number into words.
     * @param int $number
     * @return string
     */
    public static function convert_number($number)
    {
        /*require_once "Numbers/Words.php";

        $words = new Numbers_Words();
        return $words->toWords($number);*/

        if (($number < 0) || ($number > 9999999999))
        {
            throw new Exception("Number is out of range");
        }

        $Bn = floor($number / 1000000000);  /* Billions (tera) */
        $number -= $Bn * 1000000000;
        $Gn = floor($number / 1000000);  /* Millions (giga) */
        $number -= $Gn * 1000000;
        $kn = floor($number / 1000);     /* Thousands (kilo) */
        $number -= $kn * 1000;
        $Hn = floor($number / 100);      /* Hundreds (hecto) */
        $number -= $Hn * 100;
        $Dn = floor($number / 10);       /* Tens (deca) */
        $n = $number % 10;               /* Ones */

        $res = "";

        if ($Bn)
        {
            $res .= Common::convert_number($Bn) . " Billion";
        }

        if ($Gn)
        {
            $res .= Common::convert_number($Gn) . " Million";
        }

        if ($kn)
        {
            $res .= (empty($res) ? "" : " ") .
            Common::convert_number($kn) . " Thousand";
        }

        if ($Hn)
        {
            $res .= (empty($res) ? "" : " ") .
            Common::convert_number($Hn) . " Hundred";
        }

        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six",
        "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen",
        "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen",
        "Nineteen");
        $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty",
        "Seventy", "Eighty", "Ninety");

        if ($Dn || $n)
        {
            if (!empty($res))
            {
                $res .= " and ";
            }

            if ($Dn < 2)
            {
                $res .= $ones[$Dn * 10 + $n];
            }
            else
            {
                $res .= $tens[$Dn];
                if ($n)
                {
                    $res .= "-" . $ones[$n];
                }
            }
        }

        if (empty($res))
        {
            $res = "zero";
        }

        return $res;
    }

    public static function isWeekend($date = null)
    {
    	$date = $date === null ? time() : $date;
    	$day = date("N", $date);
    	return $day == 6 || $day == 7;
    }

    public static function isWorkingDay($date = null)
    {
    	$date = $date === null ? time() : $date;
    	if(Common::isWeekend($date))
    	{
    		return false;
    	}
    	else
    	{
            $holidaysModel = Model::load("system.holidays");
            $holidaysModel->queryResolve = false;
            $holidays = $holidaysModel->get();
            foreach($holidays as $holiday)
            {
                if($holiday["holiday_date"] == $date) return false;
            }
    	}
    	return true;
    }

    public static function getNextWorkingDay($numberOfDays,$date=null, $previous = false)
    {
        $nextWorkingDay = 0;
        if($date==null) $date = strtotime(date("Y-m-d", Common::time()));
        $holidaysModel = Model::load("system.holidays");
        $holidaysModel->queryResolve = false;
        $holidays = $holidaysModel->get();
        $daysCounted = 0;
        $factor = $previous === true ? -1 : 1;

        do
        {
            $nextWorkingDay+=$factor;
            $daysCounted++;

            if(date("N", $date+($nextWorkingDay*86400))==6)
            {
                $nextWorkingDay += ($factor == -1 ? -1 : 2);
            }
            else if(date("N",$date+($nextWorkingDay*86400))==7)
            {
                $nextWorkingDay += ($factor == -1 ? -2 : 1);
            }

            foreach($holidays as $holiday)
            {
                if($holiday["holiday_date"] == $date + ($nextWorkingDay*86400))
                {
                    $nextWorkingDay += $factor;
                }
            }
        }
        while($daysCounted < $numberOfDays);
        return $date + ($nextWorkingDay*86400);
    }

    public static function time()
    {
    	global $forcedTime;
    	return $forcedTime != null ? $forcedTime : time();
    }

    public static function round( $value, $precision=0 )
    {
        // If the precision is 0 then default the factor to 1, otherwise
        // use 10^$precision. This effectively shifts the decimal point to the
        // right.
        if ( $precision == 0 ) {
            $precisionFactor = 1;
        }
        else {
            $precisionFactor = pow( 10, $precision );
        }

        // ceil doesn't have any notion of precision, so by multiplying by
        // the right factor and then dividing by the same factor we
        // emulate a precision
        return round( $value * $precisionFactor )/$precisionFactor;
    }
    
    /**
     * 
     */
    public static function sentenceTime($time, $options = null)
    {
        $elapsed = time() - $time;

        if($elapsed < 10)
        {
            $englishDate = 'now';
        }
        elseif($elapsed >= 10 && $elapsed < 60)
        {
            $englishDate = "$elapsed seconds";
        }
        elseif($elapsed >= 60 && $elapsed < 3600)
        {
            $minutes = floor($elapsed / 60);
            $englishDate = "$minutes minutes";
        }
        elseif($elapsed >= 3600 && $elapsed < 86400)
        {
            $hours = floor($elapsed / 3600);
            $englishDate = "$hours hour" . ($hours > 1 ? 's' : '');
        }
        elseif($elapsed >= 86400 && $elapsed < 172800)
        {
            $englishDate = "yesterday";
        }
        elseif($elapsed >= 172800 && $elapsed < 604800)
        {
            $days = floor($elapsed / 86400);
            $englishDate = "$days days";
        }
        elseif($elapsed >= 604800 && $elapsed < 2419200)
        {
            $weeks = floor($elapsed / 604800);
            $englishDate = "$weeks weeks";
        }
        elseif($elapsed >= 2419200 && $elapsed < 31536000)
        {
            $months = floor($elapsed / 2419200);
            $englishDate = "$months months";
        }
        elseif($elapsed >= 31536000)
        {
            $years = floor($elapsed / 31536000);
            $englishDate = "$years years";
        }

        switch($options['elaborate_with'])
        {
            case 'ago':
                if($englishDate != 'now' && $englishDate != 'yesterday')
                {
                    $englishDate .= ' ago';
                }
                break;
        }

        return $englishDate;
    }    
}
