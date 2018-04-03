<?php
namespace app\components\receipt;

use DateTime;
use yii\base\Widget;
use function mb_convert_encoding;
//use yii\web\NotFoundHttpException;

/**
 * Description of Receipt
 *
 * @author daddy
 */
class Receipt extends Widget
{
/** @var array Сведения о лицевом счете, квартире*/
    public $ls;
/** @var array Сведения о платежном документе лицевого счета*/
    public $payment;
/** @var ReceiptData Сведения о многоквартирном доме */
    public $data;
/** @var integer locale Текущая локаль*/
    private $locale;
/** @var array Суммирование сальдо*/
    private $sum_saldo;
    
    const LOCALE_WIN1251=1;
    const LOCALE_UTF8=2;
    const LOCALE_КОI8_R=3;
    

    public function init()
    {   
        $this->payment['payBefor'] = $this->data->payBeforData;
        $this->payment['periodPay']= $this->data->periodName;
        $this->locale= self::LOCALE_UTF8;
        $this->sum_saldo=[];
        $this->sum_saldo['saldo_in']=0;
        $this->sum_saldo['receipt']=0;
        $this->sum_saldo['accrued']=0;
        $this->sum_saldo['recalc']=0;
        $this->sum_saldo['saldo_out']=0;
    }
    public function run()
    {
        $pth=ReceiptAsset::register($this->view);
        $array_html = $this->getSaldosHtml($this->payment);
        $array_html['ipath'] = $pth->baseUrl;
        $array_html['ls'] = $this->ls;
        $array_html['payment']=  $this->payment;
        echo $this->render('receipt', $array_html);
    }

    protected function getQrText() {
        $text="ST0001";
        $text.=$this->locale;
        $text.="|Name=". $this->payment['org_name'];
        $text.="|PersonalAcc=". $this->payment['rs'];
        $text.="|BankName=". $this->payment['bank_name'];
        $text.="|BIC=".$this->payment['bik'];
        $text.="|CorrespAcc=". $this->payment['ks'];
//        $text.="|Sum=".(int)round((floatval($this->sum_saldo['saldo_out'])+floatval($this->payment['penalty_out']))*100);
        $text.="|Sum=".(int)round(floatval($this->sum_saldo['saldo_out'])*100);
        $text.="|Purpose=". $this->payment['name'];
        $text.="|PayeeINN=". $this->payment['inn'];
        $text.="|TaxPayKind=02";
        $text.="|LastName=". $this->ls['last_name'];
        $text.="|FirstName=". $this->ls['first_name'];
        $text.="|MiddleName=". $this->ls['middle_name'];
        $text.="|PersAcc=".str_pad(($this->ls['ls_id']), 8, "0",STR_PAD_LEFT);
        $text.="|PaymPeriod=". $this->payment['periodPay'];
        $text.="|Category=". $this->payment['service_id'];
        if ($this->locale== self::LOCALE_WIN1251) {
            $text=mb_convert_encoding($text, 'cp1251', 'UTF-8');
            $text=htmlspecialchars($text, ENT_QUOTES, 'cp1251');
        } elseif($this->locale== self::LOCALE_UTF8) {
            $text=htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        } elseif($this->locale== self::LOCALE_КОI8_R) {
            $text=mb_convert_encoding($text, 'KOI8-R', 'UTF-8');
            $text=htmlspecialchars($text, ENT_QUOTES, 'KOI8-R');
        }
        return $text;
    }

    protected function getSaldosHtml() {
        $house_norms=[];
        $house_ploschad_show=FALSE;
        $appr_meters_groups=[];
        if (empty($this->payment['service_header'])) {
//            var_dump( $this->payment['service_header']);
            $servise_header='Расчет размера платы за содержание и ремонт жилого помещения и коммунальные услуги, руб.';
        } else {
            $servise_header=$this->payment['service_header'];
        }
        
        $saldo_html='<table>'
                . '<caption>'.$servise_header.'</caption>'
                . '<thead><tr style="word-wrap: break-word;">'
                . '<th>Вид услуги</th>'
                . '<th>Вход.&shy;сальдо</th>'
                . '<th>Поступ&shy;ления</th>'
                . '<th>Количе&shy;ство</th>'
                . '<th>Колич.<br> на дом</th>'
                . '<th>Тариф</th><th>Начис&shy;ление</th>'
                . '<th>Перера&shy;счет</th>'
                . '<th>К оплате</th>'
                . '</tr>'
                . '</thead>';
        $saldos=$this->getSaldos();
        foreach ($saldos as $saldo) {
            $this->sum_saldo['saldo_in']+=floatval($saldo['saldo_in']);
            $this->sum_saldo['receipt']+=floatval($saldo['receipt']);
            $this->sum_saldo['accrued']+=floatval($saldo['accrued']);
            $this->sum_saldo['recalc']+=floatval($saldo['recalc']);
            $this->sum_saldo['saldo_out']+=floatval($saldo['saldo_out']);
            if ($saldo['duration']!=0 && $saldo['source_id']==4) {
                $appr_meters_groups[]=$saldo['group_id'];
            }
            $saldo_html.="<tr>";
            if (empty($saldo['message_saldo'])) {
                $saldo_html.="<td>{$saldo['usluga_name']}</td>";
            } else {
                $saldo_html.="<td>{$saldo['usluga_name']}<br>{$saldo['message_saldo']}</td>";
            }
            $saldo_html.='<td>'.number_format($saldo['saldo_in'],2).'</td>'
                    .'<td>'.number_format($saldo['receipt'],2).'</td>'
                    .'<td>'.round($saldo['amount'],4).' '.$saldo['unit'].'</td>';
            if ($saldo['duration']!=0 && $saldo['source_id']==5 && array_key_exists($saldo['group_id'], $this->data->metersHouseValues)) {
                $saldo_html.="<td>".round(($this->data->metersHouseValues[$saldo['group_id']]),4)." ".$saldo['unit']."</td>";
            } elseif ($saldo['duration']!=0 && $saldo['source_id']==7 && array_key_exists($saldo['usluga_id'], $this->data->serviceHouseValues)) {
                $saldo_html.="<td>".round($this->data->serviceHouseValues[$saldo['usluga_id']],4)." ".$saldo['unit']."</td>";
            } elseif ($saldo['duration']!=0 && ($saldo['source_id']==9) && array_key_exists($saldo['usluga_id'], $this->data->serviceHouseValues)) {
                $saldo_html.="<td>".round($this->data->serviceHouseValues[$saldo['usluga_id']],4)." ".$saldo['unit']."</td>";
            } elseif ($saldo['duration']!=0 && ($saldo['source_id']==10) && array_key_exists($saldo['group_id'], $this->data->houseNorms)) {
                $saldo_html.="<td>".round($this->data->houseNorms[$saldo['group_id']]*$this->data->house['ploschad_odn_service'],4)." ".$saldo['unit']."</td>";
            } else {
                $saldo_html.="<td>---</td>";
            }
           $saldo_html.='<td>'.number_format($saldo['tariff'],2).'</td>'
           . "<td>".number_format($saldo['accrued'],2)."</td>"
           . "<td>".number_format($saldo['recalc'],2)."</td>"
           . "<td>".number_format($saldo['saldo_out'],2)."</td>"
                   ;
           if (($saldo['source_id']==10 || $saldo['source_id']==9)) {
               $house_ploschad_show=TRUE;
           }
           if (is_array($this->data->houseNorms) && array_key_exists($saldo['group_id'], $this->data->houseNorms)) {
               $house_norms[$saldo['group_name']]['odpu']=$this->data->houseNorms[$saldo['group_id']];
           }
           if (is_array($this->data->houseNormsPerson) && array_key_exists($saldo['group_id'], $this->data->houseNormsPerson)) {
               $house_norms[$saldo['group_name']]['ipu']=$this->data->houseNormsPerson[$saldo['group_id']];
           }
        }
        $saldo_html.='<tr>'
                . '<td>Итого</td>'
                . '<td>'.number_format($this->sum_saldo['saldo_in'],2).'</td>'
                . '<td>'.number_format($this->sum_saldo['receipt'],2).'</td>'
                . '<td>---</td><td>---</td>'
                . '<td>---</td>'
                . '<td>'.number_format($this->sum_saldo['accrued'],2).'</td>'
                . '<td>'.number_format($this->sum_saldo['recalc'],2).'</td>'
                . '<td style="text-align: center;font-weight: bold;">'.number_format($this->sum_saldo['saldo_out'],2).'</td>'
                . '</tr>'
                . '</table>'
            ;
        return array(
            'saldo_html' => $saldo_html,
            'meters_appr_html' => $this->getMetersApprHtml(implode(',',array_unique($appr_meters_groups))),
            'sum_saldo_html' => $this->getSumSaldoHtml(),
            'house_norms' => $this->getHouseHormsHtml($house_norms, $house_ploschad_show),
            'qrcode_text' => $this->getQrText(),
            'barcode_text' => $this->getBarcode(),
        );
    }
    protected function getSumSaldoHtml() {
        $sum_saldo_html=''
            .'<table class="payment">'
            . '<caption>Расчет платы за услуги, руб.</caption>'
              . '<tr><td class="rigth-border">'
                . '<table>'
                . '<tr><td>Задолженность на начало периода</td><td>'.number_format($this->sum_saldo['saldo_in'],2).'</td></tr>'
                . '<tr><td>В том числе пени</td><td>'.number_format($this->payment['penalty_in'],2,'.',' ').'</td></tr>'
                . '<tr><td>Начислено</td><td>'.number_format($this->sum_saldo['accrued'],2).'</td></tr>'
                . '<tr><td>К оплате</td><td style="text-align: center;font-weight: bold;">'.number_format($this->sum_saldo['saldo_out'],2).'</td></tr>'
                . '<tr><td>Начислено пени</td><td>'.number_format($this->payment['penalty_calc'],2,'.',' ').'</td></tr>'
                . '<tr style="text-align: center;font-weight: bold;"><td>Итого к оплате за период</td><td>'.number_format($this->sum_saldo['saldo_out']+$this->payment['penalty_out'],2,'.',' ').'</td></tr>'
                . '</table>'
            . '</td><td class="none-border">'
                .'<table>'
                . '<tr><td>Наличие средств на начало периода</td><td>'.number_format($this->payment['saldo_in'],2).'</td></tr>'
                . '<tr><td>Поступления</td><td>'.number_format($this->payment["receipt"],2).'</td></tr>'
                . '<tr><td>Погашено задолженность</td><td>'.number_format($this->sum_saldo['receipt'],2).'</td></tr>'
                . '<tr><td>Погашено пени</td><td>'.number_format($this->payment['penalty_receipt'],2).'</td></tr>'
                . '<tr><td>Остаток средств</td><td>'.number_format($this->payment['saldo_out'],2).'</td></tr>'
                . '</table>'
                . '</td></tr>'
            . '</table>'
        ;
        return $sum_saldo_html;
    }
    protected function getMetersApprHtml($appr_meters_groups) {
        $meters_appr=$this->getMetersAppr($appr_meters_groups);
        if ($meters_appr==NULL) {
            return NULL;
        }
        $meters_appr_html="<table>"
                . "<caption>Показания квартирных приборов учета</caption>"
                . "<thead>"
                . "<tr>"
                . "<th>Заводской номер</th>"
                . "<th>Услуга</th>"
                . "<th>ОТ</th>"
                . "<th>ДО</th>"
                . "<th>Текущие</th>"
                . "<th>Сообщение</th>"
                . "</tr>"
                . "</thead>";
        foreach ($meters_appr as $meter) {
            $meter['value_previos']+=0;
            $meter['value_current']+=0;
            $meters_appr_html.="<tr>"
                    . "<td>{$meter['meter_number']}</td>"
                    . "<td>{$meter['group_name']}</td>"
                    . "<td>".number_format($meter['value_previos'], 2)."</td>"
                    . "<td>".number_format($meter['value_current'], 2)."</td>"
                    . "<td></td>"
                    . "<td>{$meter['message']}</td>"
                    . "</tr>";
        }
        $meters_appr_html.="</table>";
        return $meters_appr_html;
    }
    protected function getMetersAppr($appr_meters_groups) {
        if (empty($appr_meters_groups)) {
            $appr_meters_groups='0';
        }
        $sql='SELECT t1.meter_name'
                . ', t1.meter_number'
                . ', t1.value_previos'
                . ', t1.value_current'
                . ', t1.message'
                . ', t2.group_name'
                . " FROM {$this->data->getTableName('meter_appr')} t1"
                . ' INNER JOIN usluga_group t2 ON(t1.group_id=t2.group_id)'
                . ' WHERE t1.appr_id=:appr_id'
                        . " AND t1.group_id IN({$appr_meters_groups})"
                        . ' AND (((t1.beg_date IS NOT NULL) AND (t1.beg_date<:period)) AND ((t1.end_date IS NULL AND t1.status_id=10) OR (t1.end_date>=:period)))'
            ;
        $params=[':appr_id'=> $this->ls['appr_id'], 'period'=> $this->data->after_1Month];
        return $this->data->cnn->createCommand($sql, $params)->queryAll();
    }
    
    protected function getHouseHormsHtml($house_norms, $house_ploschad_show) {
        $rv='<table>';
        $rv.='<tr><td>Пени:</td><td>'.number_format($this->payment['penalty_out'],2,'.',' ').'</td></tr>';
        $rv.='<tr style="text-align: center;font-weight: bold;"><td>Итого к оплате за ';$rv.= $this->payment['periodPay'].':</td><td>'. number_format($this->sum_saldo['saldo_out']+ $this->payment['penalty_out'],2,'.',' ').'</td></tr>';
        $rv.='</table>';
//            var_dump($house_ploschad_show);            \Yii::$app->end();
        if ($house_ploschad_show) {
            $rv.='<table>';
            $rv.='<caption>Справочная информация</caption>';
            $rv.="<tr><th>Наименование</th><th>Значение</th></tr>";
//            var_dump($this->data->house);            \Yii::$app->end();
            $rv.='<tr><td>Площадь МКД</td><td>';$rv.=empty($this->data->house['ploschad_old']) ? 'Нет данных': number_format($this->data->house['ploschad_old'],2,'.',' ');$rv.='</td></tr>';
            $rv.='<tr><td>Площадь общедомового имущества для расчета СОИ</td><td>';
            $rv.=(empty($this->data->house['ploschad_odn_service'])) ? 'Нет данных': number_format($this->data->house['ploschad_odn_service'],2,'.',' ');$rv.='</td></tr>';
            $rv.='</table>';
            
        }
        if (is_array($house_norms) && count($house_norms)==0) {
            return $rv;
        }
        $rv.='<table>';
        $rv.="<tr><th>Коммунальный ресурс</th>"
                . "<th>Норматив СОИ</th>"
                . "<th>Норматив ИП</th></tr>";
        foreach ($house_norms as $key => $value) {
            $rv.="<tr><td>{$key}</td>";
            if (array_key_exists('odpu', $value)) {
                $rv.="<td>".number_format($value['odpu'],3,'.',' ')."</td>";
            } else {
                $rv.="<td></td>";
            }
            if (array_key_exists('ipu', $value)) {
                $rv.="<td>".number_format($value['ipu'],2,'.',' ')."</td></tr>";
            } else {
                $rv.="<td></td></tr>";
            }
        }
        $rv.='</table>';
        return $rv;
    }
    
    protected function getSaldos() {
        $sql='SELECT t1.saldo_in AS saldo_in'
                .', t1.receipt AS receipt' 
                .', t1.amount AS amount' 
                .', t1.tariff AS tariff' 
                .', t1.accrued AS accrued' 
                .', t1.recalc AS recalc' 
                .', t1.penalty AS penalty' 
                .', t1.saldo_out AS saldo_out' 
                .', t1.message AS message_saldo' 
                .', t1.duration AS duration' 
                .', t2.usluga_name AS usluga_name' 
                .', t2.unit' 
                .', t2.tariff AS tariff_usluga' 
                .', t2.group_id' 
                .', t2.source_id'
                .', t2.usluga_id'
                .', (SELECT t3.group_name FROM `usluga_group` `t3` WHERE t2.group_id=t3.group_id) AS group_name'
                . ' FROM '.$this->data->getTableName('saldo').' t1'
                . ' INNER JOIN '.$this->data->getTableName('usluga').' t2 ON(t1.usluga_id=t2.usluga_id)'
                . ' WHERE t1.payment_id=:payment_id'
            ;
        $params[':payment_id']=$this->payment['payment_id'];
        return $this->data->cnn->createCommand($sql, $params)->queryAll();
    }
/**
 * @return string|null Штрих-код Псковские тепловые сети
 * (2-3)2знака-месяц
 * (4)1знак-год
 * (5-9)5знаков-код платежа, внутренний для УК
 * (10-19)10знаков-лицевой счет
 * (20-28)9знаков-платеж
 * (29-32)-уникальный код УК
 */

    protected function getBarcode() {
        if ($this->payment['barcode_sign']==1) {
            $bar='8';
            $bar.=(new DateTime($this->data->period['beg_date']))->format('m');
            $bar.=substr((new DateTime($this->data->period['beg_date']))->format('yyyy'),-1,1);
            $bar.=str_pad(($this->payment['payment_id']),5,"0",STR_PAD_LEFT);
            $bar.=str_pad(($this->ls['ls_id']),10,"0",STR_PAD_LEFT);
            $bar.=str_pad((int)round(($this->sum_saldo['saldo_out'])*100),9,"0",STR_PAD_LEFT);
            $bar.='0101';
            return $bar;
        } else {
            return NULL;
        } 
    } 
}
?>
