<?php

/**
 * Asset bundle for Receipt Widget
 * @author    leo <leonidps@yandex.com>
 * @copyright Copyright &copy; leo llp
 */

namespace app\components\receipt;

use yii\web\AssetBundle;

class ReceiptAsset extends AssetBundle
{
    
    public $sourcePath='@app/components/receipt/assets';
    public $css = [
        'css/receipt.css',
    ];
    public $publishOptions = [
        'forceCopy' => true,
    ];
//    public $js = [
//        'js/easyDateTime.js',
//    ];
    public $jsOptions=['position'=>1];
}
