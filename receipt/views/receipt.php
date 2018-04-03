<?php
/**
 * @var array $payment payment data
 * @var string $ipath url to asset folder with QR-code image
 * @var array $ls ls data
 * @var array $saldo_html 
 * @var array $meters_appr_html
 * @var array $house_norms Общедомовые нормы потребления
 * @var string $qrcode_text QR-code to be printed
 * @var string $barcode_text bar-code to be printed
*/
?>
<table class="carcas payment-document">
    <tr>
        <td class="top-left-cell">Cчет-извещение №<?php echo $payment['payment_id'];?> <br>за 
            <?php echo $payment['periodPay']; ?><br>Оплатить до: <?php echo $payment['payBefor'];?><br>
            <?php echo '<img src="'.$ipath.'/images/qrcode.php?text='.$qrcode_text.'" alt="Здесь должен быть ДШК">'; ?></td>
        <td class="top-right-cell">
            <table class="head-recipient">
                <tr><td class="r-header">Получатель: <?php echo $payment['org_name'] ?><br><?php echo $payment['contract'] ?></td><td style="vertical-align:top;">ИНН: <?php echo $payment['inn']?>, БИК: <?php echo $payment['bik']?></td></tr>
                <tr><td COLSPAN=2>Адрес: <?php echo $payment['address2']?>, Телефоны: <?php echo $payment['phones']?></td></tr>
                <tr><td COLSPAN=2 class="r-header">р/с: <?php echo $payment['rs']?>, <?php echo $payment['bank_name']?></td></tr>
                <tr><td class="r-header">Плательщик: 
                    <?php
                    if ($ls['type_id']=='1') {
                        echo $ls['org_name'];
                        
                    }
                    elseif($ls['type_id']=='0') {
                        echo $ls['fio'];
                        
                    }
                    else {
                        echo 'Нет данных.';
                        
                    }
                    ?>
                    </td><td class="r-header">Лицевой Счет: <?php echo str_pad(($ls['ls_id']), 8, "0",STR_PAD_LEFT);?></td></tr>
                <tr><td COLSPAN=2>Адрес: <?php echo $ls['address']?></td></tr>
                <?php
                    if (!empty($barcode_text)) {
                        echo '<tr><td style="text-align:center;" COLSPAN=2><img src="'.$ipath.'/images/barcode.php?text='.$barcode_text.'" alt="Здесь должен быть штрих-код" /></td></tr>';
                        echo '<tr><td style="text-align:center;" COLSPAN=2>'.$barcode_text.'</td></tr>';
                    }
                ?>
            </table>
        <?php echo $sum_saldo_html; ?>
        <?php echo $meters_appr_html;?>
            <p>Подпись плательщика____________________Оплачено:____________</p></td>
    </tr>
    <tr>
        <td class="bottom-left-cell">Cчет-квитанция №<?php echo $payment['payment_id'];?> <br> за <?php echo $payment['periodPay'] ?>
            <br>Оплатить до: <?php echo $payment['payBefor'] ?>
            <br><?php echo $payment['message_recipient'] ?>
        </td>
        <td class="bottom-right-cell">
            <table class="head-recipient">
                <tr>
                    <td class="r-header">Получатель: <?php echo $payment['org_name'] ?><br><?php echo $payment['contract'] ?></td>
                    <td style="vertical-align:top;">ИНН: <?php echo $payment['inn']?>, БИК: <?php echo $payment['bik']?></td>
                </tr>
                <tr>
                    <td COLSPAN=2>Адрес: <?php echo $payment['address2']?>, Телефоны: <?php echo $payment['phones']?></td>
                </tr>
                <tr>
                    <td COLSPAN=2 class="r-header">р/с: <?php echo $payment['rs']?>,  <?php echo $payment['bank_name']?></td>
                </tr>
                <tr>
                    <td class="r-header">Плательщик:
                        <?php
                    if ($ls['type_id']=='1') {
                        echo $ls['org_name'];
                    }
                    elseif($ls['type_id']=='0') {
                        echo $ls['fio'];
                    }
                    else {
                        echo 'Нет данных.';
                    }
                    ?>
</td>
                    <td class="r-header">Лицевой Счет: <?php echo str_pad(($ls['ls_id']), 8, "0",STR_PAD_LEFT);?></td>
                </tr>
                <tr>
                    <td COLSPAN=2>Адрес: <?php echo $ls['address']?></td>
                </tr>
                <tr>
                    <td COLSPAN=2>Площадь, кв.м.: <?php echo $ls['ploschad_appr']?>; Зарегистрировано, чел.: <?php echo $ls['registered_amount']?>.</td>
                </tr>
            </table>
            <?php echo $meters_appr_html;?>
            <?php echo $saldo_html;?>
            <?php if (!empty($payment['message_payment'])) {echo $payment['message_payment'];}?>
            <?php if (!empty($ls['message_house'])) {echo $ls['message_house'];}?>
            <?php echo $house_norms;?>
            <p>Подпись плательщика____________________Оплачено:____________</p>
        </td>
    </tr>
</table>
