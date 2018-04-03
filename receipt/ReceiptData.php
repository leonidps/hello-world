<?php

namespace app\components\receipt;

use app\components\calculations\HouseData;
use app\components\DateParser;
use DateTime;

/**
 * Description of ReceiptData
 * поставщик данных для печати квитанций
 * @author prog
 */
class ReceiptData extends HouseData{
/** @var string "дата месяц год" Оплатить до*/
public $payBeforData;
/** @var string "месяц год" - Наименование периода*/
public $periodName;

    public function init() {
        parent::init();
        $dp=new DateParser();        
        $this->periodName=trim($dp->myDate($this->period['beg_date'], $dp::FORMAT_MONTH));
        $dp->padej=2;
        $month_name=$dp->getMonthList()[(new DateTime($this->period['beg_date']))->modify("+{$this->contract['next_month_end']} Month")->format('m')];
        $this->payBeforData="{$this->contract['payment_end']} {$month_name} ".(new DateTime($this->period['beg_date']))->format('Y').'г.';
        $this->after_1Month=(new DateTime($this->period['beg_date']))->modify('+1 Month')->format('Y-m-d');
    }
/** 
 * Перечень лицевых счетов   
 * добавляются параметры лицевого счета и квартиры
 * @return array|boolean 
 * - если установлен номер лицевого счета, вернет один ЛС
 * - вернет все ЛС объекта управления
 */    
    public function getLss() {
// Сведения о плательщике ФЛ       
        $select_person=', (SELECT CONCAT(last_name," ",first_name," ",middle_name)'
                . ' FROM person'
                . ' WHERE t1.person_id=person.person_id'
                . ') AS fio';
// Сведения о плательщике ФЛ       
        $select_last=', (SELECT last_name FROM person WHERE t1.person_id=person.person_id) AS last_name';
// Сведения о плательщике ФЛ       
        $select_first=', (SELECT first_name FROM person WHERE t1.person_id=person.person_id) AS first_name';
// Сведения о плательщике ФЛ       
        $select_middle=', (SELECT middle_name FROM person WHERE t1.person_id=person.person_id) AS middle_name';
// Сведения о плательщике ЮЛ       
        $select_org=', (SELECT org_name'
                . ' FROM org INNER JOIN org_reestr ON (org.org_id=org_reestr.org_id)'
                . ' WHERE org_reestr.reestr_id=t1.reestr_id'
                . ') AS org_name';
// Сведения о количестве зарегистрированных проживающих        
        $select_register=', (SELECT count(register_id)'
                . ' FROM register'
                . ' WHERE adr4.appr_id=register.appr_id AND (register.status_id!=0)'
                . ' AND (register.beg_date<=:period AND (register.end_date IS NULL OR register.end_date>:billing_date))'
                . ') AS registered_amount';
// Сведения о квартире        
        $sql='SELECT t1.ls_id AS ls_id, t1.type_id'
                .', adr3.house_id AS house_id'
                .', adr3.message AS message_house'
                .', adr4.appr_id AS appr_id'
                .', adr4.category_id AS category_id'
                .', FORMAT(adr4.ploschad, 2) AS ploschad_appr'
                .', CONCAT(adr3.address_fias,", кв.",adr4.appr_name) AS address'
                . $select_person.$select_last.$select_first.$select_middle
                . $select_register
                . $select_org
                ." FROM {$this->getTableName('ls')} t1"
                . ' INNER JOIN adr4 ON (t1.appr_id=adr4.appr_id)'
                . ' INNER JOIN adr3 ON (adr4.house_id=adr3.house_id)'
                . ' WHERE t1.status_id=:active'
                ;
                
        $params=[];
        $params[':active']= self::STATUS_ACTIVE;
        $params[':billing_date']= $this->billing_date;
        $params[':period']= $this->period['beg_date'];
        if(!empty($this->ls_id)) {
            $sql.=' AND t1.ls_id=:ls_id';
            $params[':ls_id']= $this->ls_id;
            return $this->cnn->createCommand($sql, $params)->queryOne();
        } else {
            $sql.=' AND adr3.house_id=:house_id'                        
                . ' ORDER BY (`adr4`.`appr_name`+0), `adr4`.`appr_name`'
            ;
            $params[':house_id']=$this->house['house_id'];
            return $this->cnn->createCommand($sql, $params)->queryAll();
        }
    }
/**
 * выборка платежных документов лицевого счета
 * @return array|boolean Результат выборки: 
 * -если установлен номер платежного документа, то вернет платежный документ,
 * -если установлен номер лицевого счета, вернет все платежные документы ЛС,
 * -если ничего не выбрано вернет FALSE 
 */
    public function getPayments() {
        $sql='SELECT t1.payment_id as payment_id'
                . ', t1.receipt AS receipt'
                . ', t1.saldo_in AS saldo_in'
                . ', t1.saldo_out AS saldo_out'
                . ', t1.message AS message_payment'
                . ', t1.penalty_in AS penalty_in'
                . ', t1.penalty_calc AS penalty_calc'
                . ', t1.penalty_receipt AS penalty_receipt'
                . ', t1.penalty_out AS penalty_out'
                . ', recipient.recipient_id'
                . ', recipient.name'
                . ', recipient.rs'
                . ', recipient.bank_name'
                . ', recipient.ks'
                . ', recipient.bik'
                . ', recipient.barcode_sign'
                . ', recipient.qrcode_sign'
                . ', recipient.pay_for'
                . ', recipient.contract'
                . ', recipient.service_header'
                . ', recipient.message AS message_recipient'
                . ', recipient.service_id'
                . ', org.org_name'
                . ', org.address1'
                . ', org.address2'
                . ', org.phones'
                . ', org.inn'
                ." FROM {$this->getTableName('payment')} t1"
                . ' INNER JOIN recipient ON (t1.recipient_id=recipient.recipient_id)'
                . ' INNER JOIN org ON (recipient.org_id=org.org_id)'
                . ' WHERE 1';
        if(!empty($this->payment_id)) {
            $sql.=' AND t1.payment_id=:payment_id';
            $params[':payment_id']= $this->payment_id;
            return $this->cnn->createCommand($sql, $params)->queryOne();
        } elseif(!empty($this->ls_id)) {
            $sql.=' AND t1.ls_id=:ls_id';
            $params[':ls_id']=$this->ls_id;
            return $this->cnn->createCommand($sql, $params)->queryAll();
        } else {
            return FALSE;
        }
    }
}
