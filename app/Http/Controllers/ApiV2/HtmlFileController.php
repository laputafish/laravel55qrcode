<?php namespace App\Http\Controllers\ApiV2;

use App\Models\Menu;
use App\Models\Media;
use App\Models\Voucher;
use App\Models\TempUploadFile;
use App\Models\VoucherParticipant;

use App\Helpers\UploadFileHelper;
use App\Helpers\VoucherHelper;
use App\Helpers\TempUploadFileHelper;
use App\Helpers\TemplateHelper;
use App\Helpers\FileHelper;

use App\Imports\AgentCodeImport;

use Illuminate\Http\Request;

class HtmlFileController extends BaseController
{
  public function uploadZip(Request $request)
  {
    $status = false;
    $content = '';

    if (isset($_FILES['file'])) {
    	if ($_FILES['file']['error'] <= 0) {
    		$tempFilePath = UploadFileHelper::saveTempFile($_FILES['file']);
//    		echo 'tempFilePath = '.$tempFilePath.PHP_EOL;
				$res = TempUploadFileHelper::newTempFile($this->user->id, 0, $tempFilePath, 'zip', 'all');
//

		    $zipFolder = dirname($res['path']).'/'.$res['key'];
				\Zipper::make($res['path'])->extractTo($zipFolder);
				$htmlFile = FileHelper::getFirstFile($zipFolder);
				$htmlFileContent = file_get_contents($htmlFile);
				$adjustedContent = str_replace("\n", "", $htmlFileContent);
				$adjustedContent = str_replace("\r", "", $adjustedContent);
				$headContent = TemplateHelper::extractContent($adjustedContent, 'head');
				$styleContent = TemplateHelper::extractContent($adjustedContent, 'style');
				$bodyContent = TemplateHelper::extractContent($adjustedContent, 'body');
//				$mergedContent = TemplateHelper::embedImages($bodyContent, $zipFolder);
				
				$mergedContent = $bodyContent;
//$mergedContent = $htmlFileContent;
        $status = true;
      }
    }
    return response()->json([
      'status'=>$status,
      'result'=>[
      	'content' => $mergedContent
      ]
    ]);
  }

}